<?php
namespace Vonnda\StripePayments\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\State as AppState;
use Magento\Framework\App\Area;

class PaymentIntent extends \StripeIntegration\Payments\Model\PaymentIntent
{

    protected $session;
    protected $appState;
    protected $logger;

    public function __construct(
        \StripeIntegration\Payments\Helper\Generic $helper,
        \StripeIntegration\Payments\Helper\Rollback $rollback,
        \StripeIntegration\Payments\Helper\Subscriptions $subscriptionsHelper,
        \Magento\Framework\App\CacheInterface $cache,
        \StripeIntegration\Payments\Model\Config $config,
        \StripeIntegration\Payments\Model\StripeCustomer $customer,
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Session\Generic $session,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        AppState $appState,
        \Vonnda\StripePayments\Logger\Logger $logger
    ) {
        parent::__construct($helper, $rollback, $subscriptionsHelper, $cache, $config, $customer, $addressFactory, $quoteFactory, $quoteRepository, $eventManager, $session, $checkoutHelper);
        $this->session = $session;
        $this->appState = $appState;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param \Magento\Sales\Api\Data\OrderPaymentInterface|null $payment
     * @return array
     * @throws LocalizedException
     */
    protected function getParamsFrom($quote, $payment = null)
    {
        if ($this->config->useStoreCurrency()) {
            if ($this->helper->isMultiShipping()) {
                $amount = $payment->getOrder()->getGrandTotal();
            } else {
                $amount = $quote->getGrandTotal();
            }
            $currency = $quote->getQuoteCurrencyCode();
        } else {
            if ($this->helper->isMultiShipping()) {
                $amount = $payment->getOrder()->getBaseGrandTotal();
            } else {
                $amount = $quote->getBaseGrandTotal();
            }
            $currency = $quote->getBaseCurrencyCode();
        }
        $cents = 100;
        if ($this->helper->isZeroDecimal($currency)) {
            $cents = 1;
        }
        $this->params['amount'] = round($amount * $cents);
        $this->params['currency'] = strtolower($currency);
        $this->params['capture_method'] = $this->getCaptureMethod();
        $this->params["payment_method_types"] = ["card"]; // For now
        $this->params['confirmation_method'] = 'manual';

        /** VONNDA MODIFICATION
         * Upgrading payment intent to send over level 3 data
         * Only if order is being placed in US store
         */
        if ($quote->getStoreId() == 1) {
            $items = [];
            /** @var \Magento\Quote\Model\Quote\Item $item */
            foreach ($quote->getAllItems() as $item) {
                if ($item->getParentItemId() == null) {
                    $discount = 0;
                    if (array_key_exists($item->getItemId(), $items)) {
                        $discount = $items[$item->getItemId()]['discount_amount'];
                    }
                    $items[$item->getItemId()] = [
                        'product_code' => $item->getProductId(),
                        'product_description' => $item->getSku(),
                        'unit_cost' => round($item->getPrice() * $cents),
                        'quantity' => $item->getQty(),
                        'tax_amount' => round($item->getTaxAmount() * $cents),
                        'discount_amount' => round($item->getDiscountAmount() * $cents) + $discount
                    ];
                } else {
                    if ($item->getDiscountAmount() > 0) {
                        if (array_key_exists($item->getParentItemId(), $items)) {
                            $items[$item->getParentItemId()]['discount_amount'] += round($item->getDiscountAmount() * $cents);
                        } else {
                            $items[$item->getParentItemId()] = ['discount_amount' => round($item->getDiscountAmount() * $cents)];
                        }
                    }
                }
            }
            /** @var \Magento\Quote\Model\Quote\Address $shippingAddr */
            $shippingAddr = $quote->getShippingAddress();
            $level3 = [
                'merchant_reference' => $payment->getOrder()->getIncrementId(),
                'shipping_address_zip' => $shippingAddr->getPostcode(),
                'shipping_amount' => round(($shippingAddr->getShippingAmount() + $shippingAddr->getShippingTaxAmount()) * $cents),
                'line_items' => array_values($items)
            ];
            if ($quote->getCustomerId() != null) {
                $this->params['level3']['customer_reference'] = $quote->getCustomerId();
            }
            $total = $level3['shipping_amount'];
            foreach ($level3['line_items'] as $item) {
                $total += ($item['unit_cost'] * $item['quantity']) + $item['tax_amount'] - $item['discount_amount'];
            }
            if ($total == $this->params['amount']) {
                $this->params['level3'] = $level3;
            } else {
                $this->logger->info('Quote ID '.$quote->getId().' FAILED level3 total check');
                $this->logger->info('Quote Total '.$this->params['amount'].' != Level3 Line Item Total '.$total);
                $this->logger->info(json_encode($level3));
            }
        }
        /** END VONNDA MODIFICATION */

        $this->adjustAmountForSubscriptions();
        $statementDescriptor = $this->config->getStatementDescriptor();
        if (!empty($statementDescriptor)) {
            $this->params["statement_descriptor"] = $statementDescriptor;
        } else {
            unset($this->params['statement_descriptor']);
        }
        $shipping = $this->getShippingAddressFrom($quote);
        if ($shipping) {
            $this->params['shipping'] = $shipping;
        } else {
            unset($this->params['shipping']);
        }

        /** VONNDA MODIFICATION */
        if ($payment == null) {
            $customerId = $this->customer->getStripeId();
        } else {
            $customerId = $payment->getAdditionalInformation('customer_stripe_id') ?: $this->customer->getStripeId();
        }
        if (!empty($customerId)) {
            $this->params['customer'] = $customerId;
            $stripeCustomer = $this->customer->retrieveByStripeID($customerId);
            $this->customer->loadFromData($customerId, $stripeCustomer);
        }
        /** END VONNDA MODIFICATION */

        return $this->params;
    }

    public function confirmAndAssociateWithOrder($order, $payment)
    {
        if ($payment->getAdditionalInformation("is_recurring_subscription")) {
            return null;
        }
        $hasSubscriptions = $this->helper->hasSubscriptionsIn($order->getAllItems());
        $quote = $order->getQuote();
        if (empty($quote) || !is_numeric($quote->getGrandTotal())) {
            $this->quote = $quote = $this->quoteRepository->get($order->getQuoteId());
        }
        if (empty($quote) || !is_numeric($quote->getGrandTotal())) {
            throw new \Exception("Invalid quote used for Payment Intent");
        }
        // Save the quote so that we don't lose the reserved order ID in the case of a payment error
        $quote->save();
        // Create subscriptions if any
        $piSecrets = $this->createSubscriptionsFor($order);
        $created = $this->create($quote, $payment); // Load or create the Payment Intent
        if (!$created && $hasSubscriptions) {
            if (count($piSecrets) > 0 && $this->helper->isMultiShipping()) {
                reset($piSecrets);
                $paymentIntentId = key($piSecrets); // count($piSecrets) should always be 1 here
                return $this->redirectToMultiShippingAuthorizationPage($payment, $paymentIntentId);
            }
            // This makes sure that if another quote observer is triggered, we do not update the PI
            $this->stopUpdatesForThisSession = true;
            // We may be buying a subscription which does not need a Payment Intent created manually
            if ($this->paymentIntent) {
                $object = clone $this->paymentIntent;
                $this->destroy($order->getQuoteId());
            } else {
                $object = null;
            }
            $this->triggerAuthentication($piSecrets, $order, $payment);
            // Let's save the Stripe customer ID on the order's payment in case the customer registers after placing the order
            if (!empty($this->subscriptionData['stripeCustomerId'])) {
                $payment->setAdditionalInformation("customer_stripe_id", $this->subscriptionData['stripeCustomerId']);
            }
            return $object;
        }
        if (!$this->paymentIntent) {
            throw new LocalizedException(__("Unable to create payment intent"));
        }
        if (!$this->isSuccessfulStatus()) {
            $this->order = $order;
            $save = ($this->helper->isMultiShipping() || $payment->getAdditionalInformation("save_card"));
            $this->setPaymentMethod($payment->getAdditionalInformation("token"), $save, false);
            $params = $this->config->getStripeParamsFrom($order);
            $this->paymentIntent->description = $params['description'];
            $this->paymentIntent->metadata = $params['metadata'];

            /** VONNDA MODIFICATION */
            if($this->isFrontendOrderWithDevice($order)){
               $this->paymentIntent->setup_future_usage = "off_session";
            }
            /** END VONNDA MODIFICATION */

            if ($this->helper->isMultiShipping()) {
                $this->paymentIntent->amount = $params['amount'];
            }
            $this->updatePaymentIntent($quote);
            $confirmParams = [];
            if ($this->helper->isAdmin() && $this->config->isMOTOExemptionsEnabled()) {
                $confirmParams = ["payment_method_options" => ["card" => ["moto" => "true"]]];
            }

            try {
                $this->paymentIntent->confirm($confirmParams);
                $this->prepareRollback();
            } catch (\Exception $e) {
                $this->prepareRollback();
                $this->helper->maskException($e);
            }
            if ($this->requiresAction()) {
                $piSecrets[] = $this->getClientSecret();
            }
            if (count($piSecrets) > 0 && $this->helper->isMultiShipping()) {
                return $this->redirectToMultiShippingAuthorizationPage($payment, $this->paymentIntent->id);
            }
        }
        $this->triggerAuthentication($piSecrets, $order, $payment);
        $this->processAuthenticatedOrder($order, $this->paymentIntent);
        // If this method is called, we should also clear the PI from cache because it cannot be reused
        $object = clone $this->paymentIntent;
        $this->destroy($quote->getId());
        // This makes sure that if another quote observer is triggered, we do not update the PI
        $this->stopUpdatesForThisSession = true;

        /** VONNDA MODIFICATION */
        $this->setAdditionalInfoForCard($payment, $object->charges->data[0]);
        /** END VONNDA MODIFICATION */

        return $object;
    }

    //Note - For the purposes of FE order we can use AREA_WEBAPI_REST as the area
    protected function isFrontendOrderWithDevice($order)
    {
        $isFrontend = $this->appState->getAreaCode() === Area::AREA_WEBAPI_REST;
        $itemCollection = $order->getAllItems();
        foreach($itemCollection as $item){
            $product = $item->getProduct();
            $hasFlag = $product->getData("vonnda_subscription_device_flag");
            if($hasFlag && $isFrontend){
                return true;
            }
        }
        return false;
    }

    // Because there are some inconsistencies as to when the token is stored
    public function setAdditionalInfoForCard($payment, $charge)
    {
        $payment->setAdditionalInformation("payment_code", $charge->payment_method);
    }

    public function differentFrom($quote)
    {
        // This paymentIntent is NULL for the cron
        if (!$this->paymentIntent) {
            return false;
        }
        $isAmountDifferent = ($this->paymentIntent->amount != $this->params['amount']);
        $isCurrencyDifferent = ($this->paymentIntent->currency != $this->params['currency']);
        $isPaymentMethodDifferent = !$this->samePaymentMethods($this->params['payment_method_types']);
        $isAddressDifferent = $this->isAddressDifferentFrom($quote);
        return ($isAmountDifferent || $isCurrencyDifferent || $isPaymentMethodDifferent || $isAddressDifferent);
    }

    /**
     * Overriding this function to comment out setting params.
     * When a payment is declined, the system still has the payment intent for the current quote in cache and loads it from stripe
     * on the second attempt. In the loadFromCache() function it checks if ($this->isInvalid($quote) || $this->hasPaymentActionChanged())
     * and if so runs this delete function, which cancels the payment intent in stripe and removes the cached info, and for seemingly no
     * apparent reason sets params to empty which then causes an error when params['amount'] doesnt exist.
     * Then when the order is placed again there's no defunct payment intent to load and delete, so params are never set to empty.
     * @param $quoteId
     * @param bool $cancelPaymentIntent
     */
    public function destroy($quoteId, $cancelPaymentIntent = false)
    {
        $key = 'payment_intent_' . $quoteId;
        $this->session->unsetData($key);
        if ($this->paymentIntent && $cancelPaymentIntent && $this->paymentIntent->status != $this::CANCELED) {
            $this->paymentIntent->cancel();
        }
        $this->paymentIntent = null;
        // $this->params = [];
        if (isset($this->paymentIntentsCache[$key])) {
            unset($this->paymentIntentsCache[$key]);
        }
    }
}