<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\StripePayments\Helper;

use StripeIntegration\Payments\Helper\Api as StripePaymentsApiHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\CouldNotSaveException;

class Api extends StripePaymentsApiHelper
{

    //This is an override
    //Stripe will not allow us to make a backend payment without passing the stripe_customer_id
    public function createCharge($payment, $amount, $capture, $useSavedCard = false)
    {
        try
        {
            $order = $payment->getOrder();
            $data = $this->getPaymentDetailsFrom($payment);

            $switchSubscription = $payment->getAdditionalInformation('switch_subscription');

            if ($switchSubscription)
            {
                $this->_eventManager->dispatch('stripe_subscriptions_switch_subscription', array(
                    'payment' => $payment,
                    'order' => $order,
                    'switchSubscription' => $switchSubscription
                ));
                return;
            }
            else if ($useSavedCard) // We are coming here from the admin, capturing an expired authorization
            {
                $customer = $this->_stripeCustomer->loadFromData($data['customer_id'], $data['customer']);
                $token = $data['token'];
                $this->customerStripeId = $data['customer_id'];

                if (!$token || !$this->customerStripeId)
                {
                    // The exception will be caught and silenced, so we explicitly add an error too
                    $this->helper->addError("The authorization has expired and the customer has no saved cards to re-create the order");
                    throw new LocalizedException(__("The authorization has expired and the customer has no saved cards to re-create the order."));
                }
            }
            else
            {
                $token = $payment->getAdditionalInformation('token');

                if ($this->helper->hasSubscriptions())
                {
                    // Ensure that a customer exists in Stripe (may be the case with Guest checkouts)
                    if (!$this->_stripeCustomer->getStripeId())
                    {
                        try
                        {
                            $this->_stripeCustomer->createStripeCustomer($order);

                            /** VONNDA MODIFICATION */
                            if (strpos($token, 'card_') !== 0) {
                                $card = $this->_stripeCustomer->addSavedCard($token);
                                if ($card) {
                                    $token = $card->id;
                                }
                            }
                            /** END VONNDA MODIFICATION */
                        }
                        catch (\StripeIntegration\Payments\Exception\SilentException $e)
                        {
                            return;
                        }
                    }
                }
            }

            $params = $this->config->getStripeParamsFrom($order);

            $params["source"] = $token;
            $params["capture"] = $capture;
            $params["customer"] = $data['customer_id'];

            /** VONNDA MODIFICATION */
            $customerStripeId = false;
            if ($payment->getAdditionalInformation('customer_stripe_id')) {
                $params["customer"] = $payment->getAdditionalInformation('customer_stripe_id');
                // Because this gets set and unset too many times - we can store it as another key if necessary
                $customerStripeId = $payment->getAdditionalInformation('customer_stripe_id');
            } else if ($this->_stripeCustomer->getStripeId()) {
                $params["customer"] = $this->_stripeCustomer->getStripeId();
                $payment->setAdditionalInformation('customer_stripe_id', $this->_stripeCustomer->getStripeId());
            }
            /** END VONNDA MODIFICATION */
            $this->validateParams($params);

            /* I dont think this is needed, is not in OG function
            if ($this->config->getSecurityMethod() < 1)
                unset($params['customer']);

            $amount = $params['amount'];
            $currency = $params['currency'];
            $cents = 100;
            if ($this->helper->isZeroDecimal($currency))
                $cents = 1;

            $returnData = new \Magento\Framework\DataObject();
            $returnData->setAmount($amount);
            $returnData->setParams($params);
            $returnData->setCents($cents);
            $returnData->setIsDryRun(false);

            $this->_eventManager->dispatch('stripe_create_subscriptions', array(
                'order' => $order,
                'returnData' => $returnData
            ));

            $params = $returnData->getParams();
            */
            $fraud = false;

            $statementDescriptor = $this->config->getStatementDescriptor();
            if (!empty($statementDescriptor))
                $params["statement_descriptor"] = $statementDescriptor;

            if ($params["amount"] > 0)
            {
                /** VONNDA MODIFICATION */
                // added for cron to work
                if ($customerStripeId && !isset($params['customer'])) {
                    $params['customer'] = $customerStripeId;
                }
                /** END VONNDA MODIFICATION */

                if (strpos($token, "pm_") === 0)
                {
                    $quoteId = $payment->getOrder()->getQuoteId();

                    if ($useSavedCard)
                    {
                        // We get here if an existing authorization has expired, in which case
                        // we want to discard old Payment Intents and create a new one
                        $this->paymentIntent->refreshCache($quoteId);
                        $this->paymentIntent->destroy($quoteId, true);
                    }

                    $quote = $this->quoteFactory->create()->load($quoteId);
                    $this->paymentIntent->quote = $quote;

                    // This in theory should always be true
                    if ($capture)
                        $this->paymentIntent->capture = \StripeIntegration\Payments\Model\PaymentIntent::CAPTURE_METHOD_AUTOMATIC;
                    else
                        $this->paymentIntent->capture = \StripeIntegration\Payments\Model\PaymentIntent::CAPTURE_METHOD_MANUAL;

                    if (!$this->paymentIntent->create())
                        throw new \Exception("The payment intent could not be created");

                    $this->paymentIntent->setPaymentMethod($token);
                    $pi = $this->paymentIntent->confirmAndAssociateWithOrder($payment->getOrder(), $payment);
                    if (!$pi)
                        throw new \Exception("Could not create a Payment Intent for this order");

                    $charge = $this->retrieveCharge($pi->id);
                }
                else
                    $charge = \Stripe\Charge::create($params);

                $this->rollback->addCharge($charge->id);

                if ($this->config->isStripeRadarEnabled() &&
                    isset($charge->outcome->type) &&
                    $charge->outcome->type == 'manual_review')
                {
                    $payment->setAdditionalInformation("stripe_outcome_type", $charge->outcome->type);
                }

                if (!$charge->captured && $this->config->isAutomaticInvoicingEnabled())
                {
                    $payment->setIsTransactionPending(true);
                    $invoice = $order->prepareInvoice();
                    $invoice->register();
                    $order->addRelatedObject($invoice);
                }

                $payment->setTransactionId($charge->id);
                $payment->setLastTransId($charge->id);
            }

            $payment->setIsTransactionClosed(0);
            $payment->setIsFraudDetected($fraud);
        }
        catch (\Stripe\Exception\CardException $e)
        {
            $this->rollback->run();
            throw new CouldNotSaveException(__($e->getMessage()));
        }
        catch (\Stripe\Error $e)
        {
            $this->rollback->run();
            throw new CouldNotSaveException(__($e->getMessage()));
        }
        catch (\Exception $e)
        {
            $this->rollback->run();

            if ($this->helper->isAdmin())
                throw new CouldNotSaveException(__($e->getMessage()));
            else
                throw new CouldNotSaveException(__("Sorry, an payment error has occurred, please contact us for support."));
        }
    }

}