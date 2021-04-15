<?php

namespace Vonnda\TealiumTags\CustomerData;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Tealium\Tags\Helper\Product;

class JsSaveOrder extends \Tealium\Tags\CustomerData\JsSaveOrder
{
    /** @var Payment\Transaction\Repository */
    protected $transactionRepository;

    public function __construct(
        CustomerSession $customerSession,
        Product $productHelper,
        OrderRepositoryInterface $orderRepository,
        Payment\Transaction\Repository $transactionRepository
    ) {
        parent::__construct($customerSession, $productHelper, $orderRepository);

        $this->transaction = $transactionRepository;
    }

    public function getSectionData()
    {
        $result = [];
        //getOrderConfirmation is currently doing this
        return $result;
        
        $orderId = $this->_customerSession->getTealiumCheckout();
        $this->_customerSession->unsTealiumCheckout();

        if(gettype($orderId) === 'array' && (count($orderId) > 0)){
            $orderId = $orderId[0];
        } elseif(gettype($orderId) === 'array' && count($orderId) === 0){
            $orderId = false;
        }

        if ($orderId) {

            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->_orderRepository->get($orderId);

            /** @var Payment $orderPayment */
            $orderPayment = $order->getPayment();

            $result = [
                'data'=>[
                    'product_category' => [],
                    'product_discount' => [],
                    'product_id' => [],
                    'product_list_price' => [],
                    'product_name' => [],
                    'product_quantity' => [],
                    'sku' => [],
                    'product_subcategory' => [],
                    'product_unit_price' => [],
                    'product_brand' => [],
                    'product_image_url' => [],
                    'product_price' => [],
                    'product_promo_code' => [],
                    'product_serial_number' => [],
                    'cart_id'=> $orderId,
                    'tealium_event'=>'purchase'
                ]
            ];

            $result['data']['ab_test_group'] = "";
            $result['data']['country_code'] = "us";
            $result['data']['event_action'] = "Completed Transaction";

            if ($orderPayment->getIsTransactionPending()) {

                $transactionId = $orderPayment->getTransactionId();

                /** @var Payment\Transaction $transaction */
                $transaction = $this->transactionRepository->getByTransactionId(
                    $transactionId,
                    $orderPayment->getId(),
                    $order->getId()
                );

                $errorMessage = $transaction->getAdditionalInformation(Transaction::RAW_DETAILS);

                $result['data']['event_action'] = "Transaction Failed";
                $result['data']['error_message'] = $errorMessage;
                $result['data']['event_label'] = "Transaction Failed";
            }

            $result['data']['event_category'] = "Ecommerce";
            $result['data']['language_code'] = "en";
            $result['data']['offer_name'] = "";
            $result['data']['page_type'] = "checkout";
            $result['data']['payment_method'] = $order->getPayment()->getMethod();


            foreach ($order->getAllItems() as $item) {
                $productData = $this->_productHelper->getProductData($item->getProductId());
                array_push($result['data']['product_category'], $productData['product_category'][0]);
                array_push($result['data']['product_discount'], $productData['product_discount'][0]);
                array_push($result['data']['product_name'], $productData['product_name'][0]);
                array_push($result['data']['product_id'], $item->getProductId());
                array_push($result['data']['product_list_price'], $productData['product_list_price'][0]);
                array_push($result['data']['product_quantity'], (string)$item->getQty());
                array_push($result['data']['sku'], $productData['product_sku'][0]);
                array_push($result['data']['product_subcategory'], $productData['product_subcategory'][0]);
                array_push($result['data']['product_unit_price'], $productData['product_unit_price'][0]);
                array_push($result['data']['product_brand'], $productData);
                array_push($result['data']['product_image_url'], $item->getProduct()->getImage());
                array_push($result['data']['product_price'], $productData['product_list_price'][0]);
                array_push($result['data']['product_promo_code'], $item->getCustomAttribute('promo_code'));
                array_push($result['data']['product_serial_number'], $item->getCustomAttribute('serial_number'));
            }

            $result = $this->getCustomerSectionData($result, $order);
            $result = $this->getOrderSectionData($result, $order);
        }

        return $result;
    }

    /**
     * @param array $result
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    private function getCustomerSectionData($result, $order)
    {
        $customer = $this->_customerSession->getCustomer();

        $result['data']['customer_billing_zip'] = $order->getBillingAddress()->getPostcode() ?: "";
        $result['data']['customer_city'] = $order->getShippingAddress()->getCity() ?: "";
        $result['data']['customer_state'] = $order->getShippingAddress()->getRegion() ?: "";
        $result['data']['customer_country'] = $order->getShippingAddress()->getCountryId() ?: "";
        $result['data']['customer_email'] = $customer->getEmail() ?: "";
        $result['data']['customer_first_name'] = $customer->getFirstname() ?: "";
        $result['data']['customer_id'] = $customer->getId() ?: "";
        $result['data']['customer_last_name'] = $customer->getLastname() ?: "";
        $result['data']['customer_shipping_zip'] = $order->getShippingAddress()->getPostcode() ?: "";

        return $result;
    }

    /**
     * @param array $result
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    private function getOrderSectionData($result, $order)
    {
        $result['data']['order_currency_code'] = $order->getBaseCurrencyCode();
        $result['data']['order_grand_total'] = number_format($order->getGrandTotal(), 2, '.', '');
        $result['data']['order_id'] = $order->getId();
        $result['data']['order_promo_amount'] = number_format($order->getDiscountAmount(), 2, '.', '');
        $result['data']['order_promo_code'] = $order->getCouponCode();
        $result['data']['order_shipping_amount'] = $order->getShippingAmount() ? number_format($order->getShippingAmount(), 2, '.', '') : "";
        $result['data']['order_shipping_type'] = $order->getShippingMethod();
        $result['data']['order_subtotal'] = number_format($order->getSubtotal(), 2, '.', '');
        $result['data']['order_tax_amount'] = number_format($order->getTaxAmount(), 2, '.', '');
        $result['data']['order_type'] = $order->getEntityType();
        return $result;
    }
}