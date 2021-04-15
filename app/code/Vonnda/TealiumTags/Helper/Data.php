<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */
namespace Vonnda\TealiumTags\Helper;

use Vonnda\Subscription\Model\SubscriptionCustomer;
use Vonnda\Subscription\Model\SubscriptionCustomerRepository;
use Vonnda\DeviceManager\Model\DeviceManagerRepository as SubscriptionDeviceRepository;
use Vonnda\Subscription\Model\SubscriptionPayment;
use Vonnda\Subscription\Helper\Logger;
use Vonnda\Subscription\Helper\StripeHelper;

use Carbon\Carbon;

use Magento\Braintree\Model\LocaleResolver;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\App\Emulation;
use Magento\Catalog\Helper\ImageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Newsletter\Model\SubscriberFactory;
use Tealium\Tags\Helper\Product as TealiumProductHelper;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Backend\Model\Auth\Session as BackendSession;


class Data extends AbstractHelper
{
    const STORE_CODE_US = "mlk_us_sv";

    const STORE_CODE_CA = "mlk_ca_sv";
    /**
     * Http Gateway
     *
     * @var \Vonnda\TealiumTags\Model\HttpGateway $customerSession
     */
    protected $httpGateway;

    /**
     * Vonnda Logger
     *
     * @var \Vonnda\Subscription\Helper\Logger $logger
     */
    protected $logger;

    /**
     * App Emulation
     *
     * @var Emulation $appEmulation
     */
    protected $appEmulation;

    /**
     * Image Helper
     *
     * @var ImageFactory $imageHelper
     */
    protected $imageHelper;

    /**
     * Product Repository
     *
     * @var ProductRepositoryInterface $productRepository
     */
    protected $productRepository;

    /**
     * Store Repository Interface
     *
     * @var \Magento\Store\Model\StoreRepositoryInterface $storeRepository
     */
    protected $storeRepository;

    protected $categoryRepository;

    protected $subscriptionCustomerRepository;

    protected $orderRepository;

    protected $storeManager;

    protected $cart;

    protected $customerSession;

    protected $checkoutSession;

    protected $localeResolver;

    protected $customerRepository;

    protected $cartRepository;

    protected $searchCriteriaBuilder;

    protected $sortOrderBuilder;

    protected $subscriptionDeviceRepository;

    protected $subscriberFactory;

    protected $tealiumProductHelper;

    protected $stripeHelper;

    protected $addressRepository;

    protected $backendSession;

    /**
     * 
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        Logger $logger,
        Emulation $appEmulation,
        ImageFactory $imageHelper,
        ProductRepositoryInterface $productRepository,
        StoreRepositoryInterface $storeRepository,
        Context $context,
        CategoryRepositoryInterface $categoryRepository,
        SubscriptionCustomerRepository $subscriptionCustomerRepository,
        OrderRepositoryInterface $orderRepository,
        StoreManagerInterface $storeManager,
        Cart $cart,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        LocaleResolver $localeResolver,
        CustomerRepositoryInterface $customerRepository,
        CartRepositoryInterface $cartRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        SubscriptionDeviceRepository $subscriptionDeviceRepository,
        SubscriberFactory $subscriberFactory,
        TealiumProductHelper $tealiumProductHelper,
        StripeHelper $stripeHelper,
        AddressRepositoryInterface $addressRepository,
        BackendSession $backendSession
    ) {
        $this->logger = $logger;
        $this->appEmulation = $appEmulation;
        $this->imageHelper = $imageHelper;
        $this->productRepository = $productRepository;
        $this->storeRepository = $storeRepository;
        $this->categoryRepository = $categoryRepository;
        $this->orderRepository = $orderRepository;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->storeManager = $storeManager;
        $this->cart = $cart;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->localeResolver = $localeResolver;
        $this->customerRepository = $customerRepository;
        $this->cartRepository = $cartRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->subscriptionDeviceRepository = $subscriptionDeviceRepository;
        $this->subscriberFactory = $subscriberFactory;
        $this->tealiumProductHelper = $tealiumProductHelper;
        $this->stripeHelper = $stripeHelper;
        $this->addressRepository = $addressRepository;
        $this->backendSession = $backendSession;

        parent::__construct($context);
    }
    
    public function setDeviceFields($utagData, $subscription)
    {
        try {
            $device = $subscription->getDevice();
            if($device){
                $utagData['device_id'][] = $device->getEntityId();
                $utagData['serial_number'][] = $device->getSerialNumber() ? $device->getSerialNumber() : "";
            } else {
                $utagData['device_id'][] = "";
                $utagData['serial_number'][] = "";
            }
            $utagData['device_name'][] = "";
        } catch(\Exception $e){
            $this->logger->info($e->getMessage());
        }

        return $utagData;
    }

    //Get fields from either customer session or checkout session
    public function getCustomerFieldsFromSession()
    {
        $utagData = [
            'session_id' => "",
            'customer_id' => "",
            'customer_uid' => "",
            'customer_email' => ""
        ];
        try {
            $customer = $this->customerSession->getCustomer();
            $lastOrder = $this->checkoutSession->getLastRealOrder();
            $uuid = $customer->getCustomAttribute('cognito_uuid');
            
            if($customer){
                $utagData = [
                    'session_id' => $this->customerSession->getSessionId(),
                    'customer_id' => $customer->getId(),
                    'customer_uid' => ($uuid && $uuid->getValue()) ? $uuid->getValue() : "",
                    'customer_email' => $customer->getEmail()
                ];
            } elseif($lastOrder) {
                $utagData = [
                    'session_id' => $this->customerSession->getSessionId(),
                    'customer_id' => $lastOrder->getCustomerId(),
                    'customer_uid' => $this->getCustomerUid($lastOrder->getCustomerId()),
                    'customer_email' => $lastOrder->getCustomerEmail()
                ];
            }
            return $utagData;
        } catch(\Exception $e){}

        return $utagData;
    }

    public function setShippingAddressFields($utagData, $address)
    {
        try {
            $street = $address->getStreet();
            if(is_array($street)){
                $streetOne = isset($street[0]) ? $street[0] : "";
                $streetTwo = isset($street[1]) ? $street[1] : "";
            } else {
                $streetOne = $street;
                $streetTwo = "";
            }

            $streetString = $streetOne  . " " . $streetTwo;

            if(is_string($address->getRegion())){
                $region = $address->getRegion();
            } else {
                $region = $address->getRegion()->getRegionCode();
            }

            $utagData['customer_address_1_shipping'] = $streetString ?: "";
            $utagData['customer_address_2_shipping'] = "";
            $utagData['customer_zip_shipping'] = $address->getPostcode() ?: "";
            $utagData['customer_city_shipping'] = $address->getCity() ?: "";
            $utagData['customer_state_shipping'] = $region ?: "";
            $utagData['customer_country_shipping'] = $this->getCountryFromId($address->getCountryId()) ?: "";
            $utagData['customer_country_code_shipping'] = $address->getCountryId() ?: "";
            $utagData['customer_first_name_shipping'] = $address->getFirstname() ?: "";
            $utagData['customer_last_name_shipping'] = $address->getLastname() ?: "";
        } catch(\Exception $e){
            $this->logger->info($e->getMessage());
        }
        return $utagData;
    }

    public function setBillingAddressFields($utagData, $address)
    {
        try {
            $street = $address->getStreet();
            if(is_array($street)){
                $streetOne = isset($street[0]) ? $street[0] : "";
                $streetTwo = isset($street[1]) ? $street[1] : "";
            } else {
                $streetOne = $street;
                $streetTwo = "";
            }

            if(is_string($address->getRegion())){
                $region = $address->getRegion();
            } else {
                $region = $address->getRegion()->getRegionCode();
            }

            $utagData['customer_address_1_billing'] = $streetOne ?: "";
            $utagData['customer_address_2_billing'] = $streetTwo ?: "";
            $utagData['customer_zip_billing'] = $address->getPostcode() ?: "";
            $utagData['customer_city_billing'] = $address->getCity() ?: "";
            $utagData['customer_state_billing'] = $region ?: "";
            $utagData['customer_country_billing'] = $this->getCountryFromId($address->getCountryId()) ?: "";
            $utagData['customer_country_code_billing'] = $address->getCountryId() ?: "";
            $utagData['customer_first_name_billing'] = $address->getFirstname() ?: "";
            $utagData['customer_last_name_billing'] = $address->getLastname() ?: "";
        } catch(\Exception $e){
            $this->logger->info($e->getMessage());
        }
        return $utagData;
    }

    public function setShippingAddressFieldsFromLastOrder($utagData)
    {
        try {
            $order = $this->checkoutSession->getLastRealOrder();
            if($order){
                $shippingAddress = $order->getShippingAddress();
                if($shippingAddress){
                    return $this->setShippingAddressFields($utagData, $shippingAddress);
                }
            }
        } catch(\Exception $e){
            return $utagData;
        }

        return $utagData;
    }

    public function setBillingAddressFieldsFromLastOrder($utagData)
    {
        try {
            $order = $this->checkoutSession->getLastRealOrder();
            if($order){
                $billingAddress = $order->getBillingAddress();
                if($billingAddress){
                    return $this->setBillingAddressFields($utagData, $billingAddress);
                }
            }
        } catch(\Exception $e){
            return $utagData;
        }

        return $utagData;
    }

    public function setOrderFields($utagData, $order, $last4)
    {
        try {
            $payment = $order->getPayment();
            $utagData['order_currency_code'] = $order->getOrderCurrencyCode();
            $utagData['order_grand_total'] = $order->getGrandTotal() ? number_format($order->getGrandTotal(), 2, '.', '') : "";
            
            $utagData['order_id'] = $order->getId();
            $utagData['order_id_increment'] = $order->getIncrementId();
            
            $utagData['offer_name'] = $order->getCouponCode() ? $order->getCouponCode() : "";;
            $utagData['order_promo_amount'] = $order->getDiscountAmount() ? number_format($order->getDiscountAmount(), 2, '.', '') : "";
            $utagData['order_promo_code'] = $order->getCouponCode() ? $order->getCouponCode() : "";
            
            $utagData['order_shipping_amount'] = $order->getShippingAmount() ? number_format($order->getShippingAmount(), 2, '.', '') : "";
            $utagData['order_shipping_type'] = $order->getShippingMethod();
            
            $utagData['order_subtotal'] = $order->getSubtotal() ? number_format($order->getSubtotal(), 2, '.', '') : "";
            $utagData['order_subtotal_after_promo'] = $this->getAdjustedSubtotal($order);
            
            $utagData['order_tax_amount'] = $order->getTaxAmount() ? number_format($order->getTaxAmount(), 2, '.', '') : "";

            $utagData['payment_method'] = $payment ? $payment->getMethod() : "";
            $utagData['gift_purchase'] = $order->getGiftOrder() ? true : false;
            
            if($last4){
                $utagData['cc_last_4_digits'] = $last4;
            } else {
                $utagData['cc_last_4_digits'] = "";
                //This may be enabled later, right now we want to potentially avoid slow api calls
                //$utagData['cc_last_4_digits'] = $this->getLast4FromOrder($order);
            }
        } catch(\Exception $e){
            $this->logger->info($e->getMessage());
        }

        return $utagData;
    }

    public function getAdjustedSubtotal($order)
    {
        try {
            if(!$order->getSubtotal()){
                return "";
            }
            
            if(!$order->getDiscountAmount() || (abs($order->getDiscountAmount()) < 0.001)){
                return number_format($order->getSubtotal(), 2, '.', '');
            }
    
            //Case of 100% discount and on shipping too - discount amount would be greater
            if(abs($order->getDiscountAmount()) > $order->getSubtotal()){
                return number_format(0.00, 2, '.', '');
            }
    
            $newSubtotal = $order->getSubtotal() + $order->getDiscountAmount();
            return number_format($newSubtotal, 2, '.', '');
        } catch(\Error $e){
            $this->logger->info($e->getMessage());
            return number_format($order->getSubtotal(), 2, '.', '');
        }
    }

    //Only used in auto renewal charge failure event - order not complete
    public function setOrderFieldsFromQuote($utagData, $quote, $last4)
    {
        $shippingRate = $quote->getShippingRate();
        $discountAmount = $quote->getSubtotal() - $quote->getSubtotalWithDiscount();
        try {
            $utagData['order_currency_code'] = $quote->getQuoteCurrencyCode() ?: "";
            $utagData['order_grand_total'] = $quote->getGrandTotal() ? number_format($quote->getGrandTotal(), 2, '.', '') : "";
            
            $utagData['order_id'] = '';
            $utagData['order_id_increment'] = '';
            
            $utagData['offer_name'] = $quote->getCouponCode() ? $quote->getCouponCode() : "";;
            $utagData['order_promo_amount'] = $discountAmount > 0.001 ? number_format($discountAmount, 2) : "";
            $utagData['order_promo_code'] = $quote->getCouponCode() ? $quote->getCouponCode() : "";
            
            $utagData['order_shipping_amount'] = $shippingRate ? number_format($shippingRate->getPrice(), 2, '.', '') : "";
            $utagData['order_shipping_type'] = $shippingRate ? $shippingRate->getShippingMethod() : "";
            
            $utagData['order_subtotal'] = $quote->getSubtotal() ? number_format($quote->getSubtotal(), 2, '.', '') : "";
            $utagData['order_subtotal_after_promo'] = $this->getDiscountedSubtotalFromQuote($quote, $discountAmount);
            
            $utagData['order_tax_amount'] = $quote->getShippingAddress()->getBaseTaxAmount() ? number_format($quote->getShippingAddress()->getBaseTaxAmount(), 2, '.', '') : "";
            $utagData['payment_method'] = $quote->getPayment() ? $quote->getPayment()->getMethod() : "";
            $utagData['cc_last_4_digits'] = $last4 ? $last4 : '';
        } catch(\Exception $e){
            $this->logger->info($e->getMessage());
        }

        return $utagData;
    }

    public function getDiscountedSubtotalFromQuote($quote, $discountAmount)
    {
        try {
            if(!$quote->getSubtotal()){
                return "";
            }
            
            if(!$discountAmount || (abs($discountAmount) < 0.001)){
                return number_format($quote->getSubtotal(), 2, '.', '');
            }
    
            //Case of 100% discount and on shipping too - discount amount would be greater
            if(abs($discountAmount) > $quote->getSubtotal()){
                return number_format(0.00, 2, '.', '');
            }
    
            $newSubtotal = $quote->getSubtotal() - $discountAmount;
            return number_format($newSubtotal, 2, '.', '');
        } catch(\Error $e){
            $this->logger->info($e->getMessage());
            return number_format($quote->getSubtotal(), 2, '.', '');
        }
    }

    public function addProductInfoFromOrderItems($utagData, $order)
    {
        foreach($order->getAllItems() as $item){
            $itemIsSimpleInBundle = $item->getParentItemId() && ($item->getProductType() === 'simple');
            if($itemIsSimpleInBundle) continue;

            if($item->getProductType() === 'virtual') continue;
            $qty = $item->getQtyOrdered() ? (string)number_format($item->getQtyOrdered(), 0) : "";
            for ($x = 0; $x < $qty; $x++) {
                $product = $this->productRepository->getById($item->getProductId());
                $productData = $this->tealiumProductHelper->getProductData($item->getProductId());
                $productCategories = $this->getProductCategories($product);
                $utagData['product_category'][] =  $productCategories['subcategory'] ?: "";
                $utagData['product_discount_amount'][] =  $item->getDiscountAmount() ? number_format($item->getDiscountAmount(), 2, '.', '') : "";
                $utagData['product_name'][] = $productData['product_name'][0];
                $utagData['product_id'][] =  $item->getProductId();
                $utagData['product_list_price'][] = number_format($item->getOriginalPrice(), 2, '.', '');
                $utagData['product_quantity'][] = 1;
                $utagData['product_sku'][] = $productData['product_sku'][0];
                $utagData['product_brand'][] = "Molekule";
                $utagData['product_image_url'][] = $this->getProductImageUrl($product);
                $utagData['product_price'][] = number_format($item->getPrice(), 2, '.', '');
                $utagData['product_promo_code'][] = $order->getCouponCode() ? $order->getCouponCode() : "";
            }
        }

        return $utagData;
    }

    public function addProductInfoFromQuoteItems($utagData, $quote)
    {
        $quoteItems = $quote->getItemsCollection();
        foreach($quoteItems as $quoteItem){
            $product = $this->productRepository->getById($quoteItem->getProductId());
            if(!$quoteItem->getParentItemId()){
                $utagData = $this->addProductDataFromItem($quoteItem, $product, $utagData, $quoteItem->getQty(), $quote->getCouponCode());
            }
            if($quoteItem->getChildren() && $product->getTypeId() === 'bundle'){
                $children = $quoteItem->getChildren();
                $selectionCollection = $product->getTypeInstance(true)
                    ->getSelectionsCollection($product->getTypeInstance(true)->getOptionsIds($product), $product);
                foreach ($selectionCollection as $proselection) {
                    $productsArray[$proselection->getOptionId()][] = $proselection->getProductId();
                }
                $optionsCollection = $product->getTypeInstance(true)->getOptionsCollection($product);
                foreach ($optionsCollection as $options) {
                    if ($options->getDefaultTitle() == 'Subscription' && isset($productsArray[$options->getOptionId()])) {
                        foreach ($children as $child) {
                            if (in_array($child->getProductId(), $productsArray[$options->getOptionId()])) {
                                $product = $child->getProduct();
                                $qty = $quoteItem->getQty() * $child->getQty();
                                $utagData = $this->addProductDataFromItem($child, $product, $utagData, $qty, $quote->getCouponCode());
                            }
                        }
                    }
                }
            }

        }
        
        return $utagData;
    }

    public function addProductDataFromItem($item, $product, $utagData, $qty, $promoCode = null)
    {
        try {
            $productCategories = $this->getProductCategories($product);
            $utagData['product_brand'][] = 'Molekule';
            $utagData['product_id'][] = $product->getId();
            $utagData['product_discount_amount'][] = $item->getDiscountAmount() ? number_format($item->getDiscountAmount(), 2, '.', '') : "";
            $utagData['product_image_url'][] = $this->getProductImageUrl($product);
            $utagData['product_list_price'][] = $item->getBasePrice() ? number_format($item->getBasePrice(), 2, '.', '') : "";
            $utagData['product_name'][] = $item->getName();
            $utagData['product_price'][] = $item->getPrice() ? number_format($item->getPrice(), 2, '.', '') : "";
            $utagData['product_promo_code'][] = $promoCode ? $promoCode : "";
            $utagData['product_sku'][] = $item->getSku();
            $utagData['product_quantity'][] = (string)$qty;
            $utagData['product_category'][] = $productCategories['subcategory'];
        } catch(\Exception $e){
            $this->logger->info($e->getMessage());
        }

        return $utagData;
    }

    public function getCartInfo()
    {
        $quote = $this->cart->getQuote();
        $utag_data = [];
        if($quote->hasItems()){
            return $this->addCartItemsFromQuote($utag_data, $quote);
        }

        return [
            'cart_total_items' => 0,
            'cart_total_value' => 0.00,
            'cart_product_id' => [],
            'cart_product_price' => [],
            'cart_product_list_price' => [],
            'cart_product_quantity' => [],
            'cart_product_sku' => []
        ];
    }

    public function getCartInfoJSON()
    {
       return json_encode($this->getCartInfo());
    }
    
    public function addCartItemsFromQuote($utag_data, $quote)
    {
        $quoteItems = $quote->getItemsCollection();

        $utag_data['cart_total_items'] = number_format($quote->getItemsQty(), 0);
        $utag_data['cart_total_value'] = number_format($quote->getGrandTotal(), 2, '.', '');

        foreach($quoteItems as $quoteItem){
            $product = $this->productRepository->getById($quoteItem->getProductId());
            
            if($children = $quoteItem->getChildren()){
                $utag_data = $this->setCartFields($quoteItem, $utag_data, $quoteItem->getQty());
                $selectionCollection = $product->getTypeInstance(true)
                    ->getSelectionsCollection($product->getTypeInstance(true)->getOptionsIds($product), $product);
                foreach ($selectionCollection as $proselection) {
                    $productsArray[$proselection->getOptionId()][] = $proselection->getProductId();
                }
                $optionsCollection = $product->getTypeInstance(true)->getOptionsCollection($product);
                foreach ($optionsCollection as $options) {
                    if ($options->getDefaultTitle() == 'Subscription' && isset($productsArray[$options->getOptionId()])) {
                        foreach ($children as $child) {
                            $qty = $quoteItem->getQty() * $child->getQty();
                            if (in_array($child->getProductId(), $productsArray[$options->getOptionId()])) {
                                $product = $child->getProduct();
                                $utag_data = $this->setCartFields($child, $utag_data, $qty);
                            }
                        }
                    }
                }
            }
        }
 
        return $utag_data;
    }

    public function setCartFields($quoteItem, $utag_data, $qty)
    {
        try {
            $utag_data['cart_product_id'][] = $quoteItem->getProductId();
            $utag_data['cart_product_price'][] = number_format($quoteItem->getPrice(), 2, '.', '');
            $utag_data['cart_product_list_price'][] = number_format($quoteItem->getBasePrice(), 2, '.', '');
            $utag_data['cart_product_quantity'][] = number_format($qty, 0);
            $utag_data['cart_product_sku'][] = $quoteItem->getSku();
        } catch(\Exception $e){
            $this->logger->info($e->getMessage());
        }

        return $utag_data;
    }

    public function setSubscriptionFields($utagData, $subscriptionCustomer)
    {
        $subscriptionPlan = $subscriptionCustomer->getSubscriptionPlan();
        $autoRefill = $subscriptionCustomer->getStatus() === SubscriptionCustomer::AUTORENEW_ON_STATUS ? true : false;
        $freeFilterEligible = $subscriptionPlan->getNumberOfFreeShipments();

        $utagData["filter_frequency"][] = $subscriptionPlan->getFrequency();
        $utagData["next_shipment_date"][] = $subscriptionCustomer->getNextOrder();
        $utagData["filter_plan_price"][] = number_format($subscriptionPlan->getPlanPrice(), 2, '.', '');
        
        $utagData = $this->setDeviceFields($utagData, $subscriptionCustomer);
        $utagData['subscription_id'][] = (string)$subscriptionCustomer->getId();
        
        $renewalDate = $subscriptionCustomer->getRenewalDateObject();
        $utagData['filter_refill_end_date'][] = $renewalDate ? $renewalDate->format("Y-m-d") : "";

        $subscriptionPayment = $subscriptionCustomer->getPayment();
        $paymentIsValid = $subscriptionPayment 
            && $subscriptionPayment->getStatus() === SubscriptionPayment::VALID_STATUS;

        $utagData['payment_on_file'][] = $paymentIsValid ? true : false;
        $utagData['auto_refill'][] =  $autoRefill;
        $utagData['free_refill_eligible'][] = $freeFilterEligible ? true : false;

        $utagData['filter_next_charge_amount'] = number_format($subscriptionPlan->getPlanPrice(), 2, '.', ',');

        return $utagData;
    }

    public function setSubscriptionFieldsByParentOrderId($utagData, $orderId)
    {
        if(!$orderId) return $utagData;

        $subscriptionCustomers = $this->subscriptionCustomerRepository->getAllByParentOrderId($orderId);
        foreach($subscriptionCustomers as $subscriptionCustomer){
            $utagData = $this->setSubscriptionFields($utagData, $subscriptionCustomer);
        }

        return $utagData;
    }

    public function setSubscriptionFieldsByCustomerId($utagData, $customerId)
    {
        if(!$customerId) return $utagData;
        $sortOrder = $this->sortOrderBuilder
            ->setField(SubscriptionCustomer::CREATED_AT)
            ->setDirection(SortOrder::SORT_DESC)
            ->create();
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('customer_id', $customerId,'eq')
            ->addSortOrder($sortOrder)
            ->create();

        $subscriptionCustomers = $this->subscriptionCustomerRepository
            ->getList($searchCriteria)
            ->getItems();

        foreach($subscriptionCustomers as $subscriptionCustomer){
            $utagData = $this->setSubscriptionFields($utagData, $subscriptionCustomer);
        }

        return $utagData;
    }

    public function getProductImageUrl($product)
    {
        try {
            $storeCode = self::STORE_CODE_US;
            $store = $this->storeRepository->get($storeCode);
            $this->appEmulation->startEnvironmentEmulation($store->getId(), \Magento\Framework\App\Area::AREA_FRONTEND, true);
            $imageUrl = $this->imageHelper->create()
                ->init($product, 'product_thumbnail_image')->getUrl();
            $this->appEmulation->stopEnvironmentEmulation();
            return $imageUrl ? $imageUrl : "";
        } catch(\Exception $e){
            $this->logger->info($e->getMessage());
        }
    }

    public function getProductCategories($product)
    {
        $categoryCollection = $product->getCategoryCollection();
        $categoryCollection->addAttributeToSelect('name');
        foreach($categoryCollection->getItems() as $category){
            if($category->getParentId() === 0){
                return [
                    "category" => $category->getName(),
                    "subcategory" => ""
                ];
            } else {
                $parent = $this->categoryRepository->get($category->getParentId());
                return [
                    "category" => $parent->getName(),
                    "subcategory" => $category->getName()
                ];
            }
        }
        return ["category" => "", "subcategory" => ""];
    }

    public function getProductCategoriesByProductId($productId)
    {
        try {
            $product = $this->productRepository->getById($productId);
        } catch(\Exception $e){
            return ["category" => "", "subcategory" => ""];
        }
        
        $categoryCollection = $product->getCategoryCollection();
        $categoryCollection->addAttributeToSelect('name');
        foreach($categoryCollection->getItems() as $category){
            if($category->getParentId() === 0){
                return [
                    "category" => $category->getName(),
                    "subcategory" => ""
                ];
            } else {
                $parent = $this->categoryRepository->get($category->getParentId());
                return [
                    "category" => $parent->getName(),
                    "subcategory" => $category->getName()
                ];
            }
        }
        return ["category" => "", "subcategory" => ""];
    }

    public function getCountryFromId($countryId)
    {
        $countryMap = [
            'US' => 'United States',
            'CA' => 'Canada'
        ];

        if(isset($countryMap[$countryId])){
            return $countryMap[$countryId];
        }

        return '';
    }

    public function getCountryFromStore()
    {
        $store = $this->storeManager->getStore();
        if($store->getCode() === self::STORE_CODE_US){
            return "us";
        } elseif($store->getCode() === self::STORE_CODE_CA){
            return "ca";
        }

        return "";
    }

    public function addSiteInfo($utagData)
    {
        $store = $this->storeManager->getStore();
        $utagData['site_region'] = $this->localeResolver->getLocale() ?: "";
        $utagData['site_currency'] = $store->getCurrentCurrencyCode() ?: "";
        return $utagData;
    }

    public function getCustomerUid($customerId)
    {
        if(!$customerId){ return "";}
        try {
            $customer = $this->customerRepository->getById($customerId);
            $uuid = $customer->getCustomAttribute('cognito_uuid');
            $customerUid = ($uuid && $uuid->getValue()) ? $uuid->getValue() : "";
            return $customerUid;
        } catch(\Exception $e){
            return "";
        }
    }

    public function setIsBusinessAddressFromOrder($utagData, $order)
    {
        $shippingAddress = $order->getShippingAddress();
        $billingAddress = $order->getBillingAddress();

        if($shippingAddress->getCustomerAddressId()){
            $customerShippingAddress = $this->addressRepository->getById($shippingAddress->getCustomerAddressId());
            $isResidential = $customerShippingAddress->getCustomAttribute('is_residential');
            $utagData['is_business_address'] = ($isResidential && $isResidential->getValue()) ? false : true;
        }

        if($billingAddress->getCustomerAddressId()){//Shipping and billing are the same
            $customerBillingAddress = $this->addressRepository->getById($billingAddress->getCustomerAddressId());
            $isResidential = $customerBillingAddress->getCustomAttribute('is_residential');
            $utagData['is_business_address'] = ($isResidential && $isResidential->getValue()) ? false : true;
        } else {
            $utagData['is_business_address'] = "";
        }

        return $utagData;
    }

    //Directly From Customer Shipping Address - used in Cron orders
    public function setIsBusinessAddressFromShippingAddress($utagData, $shippingAddress)
    {
        try {
            $isResidential = $shippingAddress->getCustomAttribute('is_residential');
            $utagData['is_business_address'] = ($isResidential && $isResidential->getValue()) ? false : true;
            return $utagData;
        } catch(\Error $e){
            $this->logger->info($e->getMessage());
        } catch(\Exception $e){
            $this->logger->info($e->getMessage());
        }

        $utagData['is_business_address'] = "";
        return $utagData;
    }

    public function getSubscriptionInfoForLogin($customerId)
    {
        if(!$customerId) return [];
        $subscriptionFields = [];

        $subscriptionCustomers = $this->subscriptionCustomerRepository->getSubscriptionCustomersByCustomerId($customerId);
        foreach($subscriptionCustomers->getItems() as $subscriptionCustomer){
            $autoRefill = $subscriptionCustomer->getStatus() === SubscriptionCustomer::AUTORENEW_ON_STATUS ? true : false;
            
            $subscriptionFields['subscription_id'][] = (string)$subscriptionCustomer->getId();
            $subscriptionFields['auto_refill'][] =  $autoRefill;

            $subscriptionDevice = $subscriptionCustomer->getDevice();
            $subscriptionFields['serial_number'][] = 
                ($subscriptionDevice && $subscriptionDevice->getSerialNumber()) ?
                $subscriptionDevice->getSerialNumber() : "";
        }

        return $subscriptionFields;
    }

    //Easier to remove fields than to re-write functions for minor field variations
    public function unsetObjectFieldsByArray($utagData, $filterArray)
    {
        foreach($filterArray as $fieldToFilter){
            if(isset($utagData[$fieldToFilter])){
                unset($utagData[$fieldToFilter]);
            }
        }
        return $utagData;
    }

    public function addProductNamesBySubscriptionFields($utagData)
    {
        //TODO - would using getList guarantee ordering?
        foreach($utagData['device_id'] as $subscriptionDeviceId){
            $subscriptionDevice = $this->subscriptionDeviceRepository->getById($subscriptionDeviceId);
            $product = $this->productRepository->get($subscriptionDevice->getSku());
            $utagData['product_name'][] = $product->getName();
        }

        return $utagData;
    }

    public function setShippingAndBillingAddressFromOrder($utagData, $order)
    {
        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();

        if ($billingAddress !== null) {
            $street = $billingAddress->getStreet();
            if(is_array($street)){
                $streetOne = isset($street[0]) ? $street[0] : "";
                $streetTwo = isset($street[1]) ? $street[1] : "";
            } else {
                $streetOne = $street;
                $streetTwo = "";
            }

            $streetString = $streetOne  . " " . $streetTwo;

            if(is_string($billingAddress->getRegion()) || !$billingAddress->getRegion()){
                $region = $billingAddress->getRegion();
            } else {
                $region = $billingAddress->getRegion()->getRegionCode();
            }

            $countryId = $order->getBillingAddress()->getCountryId() ?: "";
            $utagData['customer_first_name_billing'] = $order->getBillingAddress()->getFirstname() ?: "";
            $utagData['customer_last_name_billing'] = $order->getBillingAddress()->getLastname() ?: "";
            $utagData['customer_city_billing'] = $order->getBillingAddress()->getCity() ?: "";
            $utagData['customer_state_billing'] = $region ?: "";
            $utagData['customer_country_billing'] = $this->getCountryFromId($countryId);
            $utagData['customer_country_code_billing'] = $countryId;
            $utagData['customer_zip_billing'] = $order->getBillingAddress()->getPostcode() ?: "";
            $utagData['customer_address_1_billing'] = $streetString;
            $utagData['customer_address_2_billing'] = "";
        }

        if ($shippingAddress !== null) {
            $street = $shippingAddress->getStreet();
            if(is_array($street)){
                $streetOne = isset($street[0]) ? $street[0] : "";
                $streetTwo = isset($street[1]) ? $street[1] : "";
            } else {
                $streetOne = $street;
                $streetTwo = "";
            }

            $streetString = $streetOne  . " " . $streetTwo;

            if(is_string($shippingAddress->getRegion())){
                $region = $shippingAddress->getRegion();
            } else {
                $region = $shippingAddress->getRegion()->getRegionCode();
            }

            $countryId = $order->getShippingAddress()->getCountryId() ?: "";
            $utagData['customer_first_name_shipping'] = $order->getShippingAddress()->getFirstname() ?: "";
            $utagData['customer_last_name_shipping'] = $order->getShippingAddress()->getLastname() ?: "";
            $utagData['customer_state_shipping'] = $region ?: "";

            $utagData['customer_city_shipping'] = $order->getShippingAddress()->getCity() ?: "";
            $utagData['customer_country_shipping'] = $this->getCountryFromId($countryId);
            $utagData['customer_country_code_shipping'] = $countryId;

            $utagData['customer_zip_shipping'] = $order->getShippingAddress()->getPostcode() ?: "";
            $utagData['customer_address_1_shipping'] = $streetString ?: "";
            $utagData['customer_address_2_shipping'] = "";
        }

        $utagData['country_code'] = $this->getCountryFromStore() 
            ?: $this->getCountryFromShippingAddress($shippingAddress);

        return $utagData;
    }

    //Not coming from store on Cron order
    public function getCountryFromShippingAddress($shippingAddress)
    {
        if(!$shippingAddress){
            return "";
        }

        $countryId = $shippingAddress->getCountryId();
        if($countryId){
            return strtolower($countryId);
        }

        return "";
    }

    public function setCustomerFieldsFromOrder($utagData, $order)
    {
        try {
            $subscriber = $this->subscriberFactory->create()->loadSubscriberDataByEmailAndStore($order->getCustomerEmail(), $order->getStoreId());
            $isSubscribed = ($subscriber && $subscriber['subscriber_status'] == 1) ? true : false;
        } catch(\Exception $e){
            $isSubscribed = false;
        }
        
        $utagData['customer_id'] = $order->getCustomerId() ? (string)$order->getCustomerId() : "";
        $utagData['customer_uid'] = $this->getCustomerUid($order->getCustomerId());
        $utagData['customer_email'] = $order->getCustomerEmail() ? $order->getCustomerEmail() : "";
        $utagData['email_preferences'] = $isSubscribed;
        $utagData['session_id'] = $this->customerSession->getSessionId();

        return $utagData;
    }

    public function getLast4FromOrder($order)
    {
        try {
            $salesOrderPayment = $order->getPayment();

            if($salesOrderPayment->getMethod() == 'cryozonic_stripe'){
                $paymentCode = $salesOrderPayment->getAdditionalInformation('payment_code');
                $card = $this->stripeHelper->getCardFromCustomerIdAndPaymentCode($order->getCustomerId(), $paymentCode);
                if($card){
                    return $card->last4;
                }

                return "";
                
            } else {
                return "";
            }
        } catch(\Error $e){
            return "";
        } catch(\Exception $e){
            return "";
        }
        
    }

    /**
     * 
     * If necessary to validate/get more info on token we can mimic functionality found in
     * Magento/Webapi/Model/Authorization/TokenUserContext @125 - 187, but unlikely to be necessary
     * since some method of authorization would have been used to get this far.
     * 
     */
    public function getEventPlatformFromAuth()
    {
        $adminUser = $this->backendSession->getUser();
        if($adminUser) return 'Admin';
        
        $isLoggedIn = $this->customerSession->isLoggedIn();
        if($isLoggedIn) return 'Account';

        return 'App';
    }

    public function getSessionId()
    {
        return $this->customerSession->getSessionId();
    }
}