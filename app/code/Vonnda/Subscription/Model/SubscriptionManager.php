<?php
namespace Vonnda\Subscription\Model;

use Vonnda\DeviceManager\Model\DeviceManagerFactory;
use Vonnda\Subscription\Helper\StripeHelper;
use Vonnda\Subscription\Helper\PromoHelper;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Vonnda\Subscription\Helper\TimeDateHelper;
use Vonnda\Subscription\Helper\Data as SubscriptionHelper;
use Vonnda\Subscription\Helper\Logger as SubscriptionLogger;
use Vonnda\Subscription\Helper\ValidationHelper;
use StripeIntegration\Payments\Model\StripeCustomerFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\State;
use Vonnda\TealiumTags\Helper\PurchaseSubdata as TealiumPurchaseEvent;
use Carbon\Carbon;

class SubscriptionManager implements \Vonnda\Subscription\Api\SubscriptionManagerInterface
{

    const LOG_DEBUG = false;

    /** @var SubscriptionCustomer $subscriptionCustomerFactory */
    protected $subscriptionCustomerFactory;

    /** @var SubscriptionCustomerRepository $subscriptionCustomerRepository */
    protected $subscriptionCustomerRepository;

    /** @var SubscriptionPaymentFactory $subscriptionPaymentFactory */
    protected $subscriptionPaymentFactory;

    /** @var SubscriptionPromoFactory $subscriptionPromoFactory */
    protected $subscriptionPromoFactory;

    /** @var SubscriptionPromoRepository $subscriptionPromoRepository */
    protected $subscriptionPromoRepository;

    /** @var SubscriptionPlanRepository $subscriptionPlanRepository */
    protected $subscriptionPlanRepository;

    /** @var DeviceManagerFactory $subscriptionDeviceFactory */
    protected $subscriptionDeviceFactory;

    /** @var StripeHelper $stripeHelper */
    protected $stripeHelper;

    /** @var PromoHelper $promoHelper */
    protected $promoHelper;

    /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /** @var TimeDateHelper $timeDateHelper */
    protected $timeDateHelper;

    /** @var SubscriptionHelper $subscriptionHelper */
    protected $subscriptionHelper;

    /** @var SubscriptionLogger $subscriptionLogger */
    protected $subscriptionLogger;

    /** @var ValidationHelper $validationHelper */
    protected $validationHelper;

    /** @var StripeCustomerFactory $stripeCustomerFactory */
    protected $stripeCustomerFactory;

    /** @var Session $customerSession */
    protected $customerSession;

    /** @var State $state */
    protected $state;

    /** @var TealiumPurchaseEvent $tealiumPurchaseEvent */
    protected $tealiumPurchaseEvent;

    /**
     * @param SubscriptionCustomerFactory $subscriptionCustomerFactory
     * @param SubscriptionCustomerRepository $subscriptionCustomerRepository
     * @param SubscriptionPaymentFactory $subscriptionPaymentFactory
     * @param SubscriptionPromoFactory $subscriptionPromoFactory
     * @param SubscriptionPromoRepository $subscriptionPromoRepository
     * @param SubscriptionPlanRepository $subscriptionPlanRepository
     * @param DeviceManagerFactory $subscriptionDeviceFactory
     * @param StripeHelper $stripeHelper
     * @param PromoHelper $promoHelper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TimeDateHelper $timeDateHelper
     * @param SubscriptionHelper $subscriptionHelper
     * @param SubscriptionLogger $subscriptionLogger
     * @param ValidationHelper $validationHelper
     * @param StripeCustomerFactory $stripeCustomerFactory
     * @param Session $customerSession
     * @param State $state
     * @param TealiumPurchaseEvent $tealiumPurchaseEvent
     */
    public function __construct(
        SubscriptionCustomerFactory $subscriptionCustomerFactory,
        SubscriptionCustomerRepository $subscriptionCustomerRepository,
        SubscriptionPaymentFactory $subscriptionPaymentFactory,
        SubscriptionPromoFactory $subscriptionPromoFactory,
        SubscriptionPromoRepository $subscriptionPromoRepository,
        SubscriptionPlanRepository $subscriptionPlanRepository,
        DeviceManagerFactory $subscriptionDeviceFactory,
        StripeHelper $stripeHelper,
        PromoHelper $promoHelper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        TimeDateHelper $timeDateHelper,
        SubscriptionHelper $subscriptionHelper,
        SubscriptionLogger $subscriptionLogger,
        ValidationHelper $validationHelper,
        StripeCustomerFactory $stripeCustomerFactory,
        Session $customerSession,
        State $state,
        TealiumPurchaseEvent $tealiumPurchaseEvent
    ){
        $this->subscriptionCustomerFactory = $subscriptionCustomerFactory;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->subscriptionPaymentFactory = $subscriptionPaymentFactory;
        $this->subscriptionPromoFactory = $subscriptionPromoFactory;
        $this->subscriptionPromoRepository = $subscriptionPromoRepository;
        $this->subscriptionDeviceFactory = $subscriptionDeviceFactory;
        $this->stripeHelper = $stripeHelper;
        $this->subscriptionPlanRepository = $subscriptionPlanRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->timeDateHelper = $timeDateHelper;
        $this->promoHelper = $promoHelper;
        $this->subscriptionHelper = $subscriptionHelper;
        $this->subscriptionLogger = $subscriptionLogger;
        $this->validationHelper = $validationHelper;
        $this->stripeCustomerFactory = $stripeCustomerFactory;
        $this->customerSession = $customerSession;
        $this->state = $state;
        $this->tealiumPurchaseEvent = $tealiumPurchaseEvent;
    }

    /**
     * Checks order for virtual products and processes
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Customer\Model\Customer $customer
     * @param bool $addSubPayment
     */
    public function processOrder($order, $customer, $addSubPayment = false)
    {
        $this->logDebug("Processing order ID:" . $order->getId());
        $itemCollection = $order->getItemsCollection();
        $subscriptionsCreated = 0;
        foreach ($itemCollection as $item) {
            if ($item->getProductType() === 'virtual') {
                for ($x=0; $x < $item->getQtyOrdered(); $x++) {
                    $subscriptionWasCreated = $this->processVirtualItem($order, $customer, $item, $addSubPayment);
                    if ($subscriptionWasCreated) $subscriptionsCreated++;
                }
            }
        }
        $this->createTealiumAfterPurchaseEvent($subscriptionsCreated, $order);
    }

    /**
     * Check item trigger sku and creates subscription if nessasary. 
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Customer\Model\Customer $customer
     * @param \Magento\Sales\Model\Order\Item $item
     * @param bool $addSubPayment
     * @return null
     */
    protected function processVirtualItem($order, $customer, $item, $addSubPayment = false)
    {
        $itemSku = $item->getSku();
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('trigger_sku',$itemSku,'eq')
            ->addFilter('store_id',$order->getStoreId(),'eq')
            ->create();
        try {
            $subscriptionPlanList = $this->subscriptionPlanRepository->getList($searchCriteria)->getItems();
            $subscriptionPlan = $this->subscriptionHelper->returnFirstItem($subscriptionPlanList);
            if($subscriptionPlan){
                $this->logDebug("Creating subscription using subscription plan ID:" . $subscriptionPlan->getId());
                $orderShippingAddress = $order->getShippingAddress();
                $customerShippingAddressId = $orderShippingAddress->getCustomerAddressId();

                $orderBillingAddress = $order->getBillingAddress();
                $customerAddressIdFromBilling = $orderBillingAddress->getCustomerAddressId();

                //Billing was set same as shipping
                if(!$customerShippingAddressId && $customerAddressIdFromBilling){
                    $customerShippingAddressId = $customerAddressIdFromBilling;
                }

                //We fall back to compare
                if(!$customerShippingAddressId){
                    $customerShippingAddress = $this->getCustomerAddress($customer, $orderShippingAddress); 
                    if($customerShippingAddress){
                        $customerShippingAddressId = $customerShippingAddress->getId();
                    }
                }
                
                if(!$customerShippingAddressId){
                    $this->log(json_encode($order->getShippingAddress()));
                    throw new \Exception('Unable to locate customer address id for shipping.');
                }
                $this->createSubscription($order->getCustomerId(), $customerShippingAddressId, $subscriptionPlan, $order, $addSubPayment);
                $this->logDebug("Finished processing order ID:" . $order->getId());
                return true;
            }
        } catch(\Exception $e){
            $this->log("Error processing subscription order with ID: " . $order->getId());
            $this->log("Customer ID: " . $customer->getId() . " Customer Email: " . $customer->getEmail());
            $this->log($e->getMessage());
        }
    }

    protected function getCustomerAddress($customer, $orderAddress)
    {   
        foreach($customer->getAddresses() as $_customerAddress){
            $same = $this->compareAddresses($_customerAddress, $orderAddress);
            if($same){
                return $_customerAddress;
            }
        }
        $this->logDebug("Customer has no addresses");
        return false;
    }

    protected function compareAddresses($customerAddress, $orderAddress)
    {
        return true;
        
        return ($customerAddress->getFirstname() == $orderAddress->getData('firstname') &&
            $customerAddress->getLastname() == $orderAddress->getData('lastname') &&
            implode("\n", $customerAddress->getStreet()) == $orderAddress->getData('street') &&
            $customerAddress->getRegionId() == $orderAddress->getData('region_id') &&
            $customerAddress->getCity() == $orderAddress->getData('city') &&
            $customerAddress->getCountryId() == $orderAddress->getData('country_id') &&
            $customerAddress->getTelephone() == $orderAddress->getData('telephone'));
    }

    /**
     * Create subscription customer and associated objects
     *
     * @param int|null $customerId
     * @param int $shippingAddressId
     * @param SubscriptionPlan $subscriptionPlan
     * @param \Magento\Sales\Model\Order $order
     * @param bool $addSubPayment
     * @return \Vonnda\Subscription\Model\SubscriptionCustomer $subscriptionCustomer
     */
    protected function createSubscription(
        int $customerId,
        int $shippingAddressId, 
        SubscriptionPlan $subscriptionPlan,
        \Magento\Sales\Model\Order $order,
        $addSubPayment = false
    ){
        //TODO - use transactions, set errors on subcustomer
        $subscriptionCustomer = $this->createSubscriptionCustomer(
            $customerId, 
            $shippingAddressId, 
            $subscriptionPlan,
            $order
        );

        if($subscriptionCustomer){
            $this->createSubscriptionDevice($order,$subscriptionCustomer);
            if ($this->customerSession->isLoggedIn() || $this->state->getAreaCode() == 'adminhtml' || $addSubPayment) {
                $this->createSubscriptionPayment($order, $subscriptionCustomer);
            }
            $this->createSubscriptionPromos($subscriptionCustomer, $subscriptionPlan);
        }

        return $subscriptionCustomer;
    }

    /**
     * Check for sku and create subscription customer
     *
     * @param int|null $customerId
     * @param int $shippingAddressId
     * @param \Vonnda\Subscription\Model\SubscriptionPlan $subscriptionPlan
     * @return \Vonnda\Subscription\Model\SubscriptionCustomer $subscriptionCustomer
     */
    protected function createSubscriptionCustomer(
        $customerId,
        int $shippingAddressId, 
        \Vonnda\Subscription\Model\SubscriptionPlan $subscriptionPlan,
        \Magento\Sales\Model\Order $order
    ){
        try {
            $subscriptionPlanId = $subscriptionPlan->getId();
            if(!$this->validationHelper->isSubscriptionPlanIdValid($subscriptionPlanId)){
                throw new \Exception('Invalid subscription plan');
            }

            $frequency = $subscriptionPlan->getFrequency();
            $frequencyUnits = $subscriptionPlan->getFrequencyUnits();
            $nextOrderDate = $this->timeDateHelper->getNextDateFromFrequency($frequency, $frequencyUnits);

            $subscriptionCustomer = $this->subscriptionCustomerFactory->create()
                ->setCustomerId($customerId)
                ->setShippingAddressId($shippingAddressId)
                ->setStatus(SubscriptionCustomer::NEW_NO_PAYMENT_STATUS)
                ->setLastOrder(null)
                ->setNextOrder($nextOrderDate)
                ->setSubscriptionPlanId($subscriptionPlanId)
                ->setParentOrderId($order->getId());
            $this->subscriptionCustomerRepository->save($subscriptionCustomer);
            $this->logDebug("Subscription ID:" . $subscriptionCustomer->getId() . " created");
            return $subscriptionCustomer;
        } catch(\Exception $e){
            $this->log("Failure creating subscription plan - " . $e->getMessage());
            return false;
        }
        
    }

    /**
     * Create subscription payment
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Vonnda\Subscription\Model\SubscriptionCustomer $subscriptionCustomer
     * @return \Vonnda\Subscription\Model\SubscriptionPayment $subscriptionPayment
     */
    protected function createSubscriptionPayment(
        \Magento\Sales\Model\Order $order,
        \Vonnda\Subscription\Model\SubscriptionCustomer $subscriptionCustomer
    ){
        $subscriptionCustomerId = $subscriptionCustomer->getId();
        $salesOrderPayment = $order->getPayment();
        $salesOrderPaymentId = $salesOrderPayment->getId();
        
        $usedStripe = $this->customerUsedStripe($salesOrderPayment);

        if($usedStripe){
            $stripeCustomerId = $this->getStripeCustomerId();
            $paymentCode = $salesOrderPayment->getAdditionalInformation('payment_code');

            $card = $this->stripeHelper->getCardFromCustomerIdAndPaymentCode($subscriptionCustomer->getCustomerId(), $paymentCode);
            $expirationDate = false;
            if($card){
                $expirationDate = $card->exp_month . "/" . $card->exp_year;
            }

            $subscriptionPayment = $this->subscriptionPaymentFactory->create()
                ->setPaymentId($salesOrderPaymentId ? $salesOrderPaymentId : null)
                ->setStripeCustomerId($stripeCustomerId ? $stripeCustomerId : null)
                ->setPaymentCode($paymentCode ? $paymentCode : null)
                ->setExpirationDate($expirationDate ? $expirationDate : null)
                ->setStatus(SubscriptionPayment::VALID_STATUS);

            $subscriptionCustomer->setStatus(SubscriptionCustomer::AUTORENEW_ON_STATUS)
                                 ->setPayment($subscriptionPayment);
            $this->subscriptionCustomerRepository->save($subscriptionCustomer);
        } else {
            $subscriptionPayment = $this->subscriptionPaymentFactory->create()
                ->setPaymentId($salesOrderPaymentId ? $salesOrderPaymentId : null)
                ->setStatus(SubscriptionPayment::INVALID_STATUS);
            $subscriptionCustomer->setStatus(SubscriptionCustomer::NEW_NO_PAYMENT_STATUS)
                                 ->setPayment($subscriptionPayment);
            $this->subscriptionCustomerRepository->save($subscriptionCustomer);

        }
        $this->logDebug("Subscription payment ID:" . $subscriptionPayment->getId() . " created");
    }

    /**
     * Check if customer used stripe
     *
     * @param \Magento\Sales\Model\Order\Payment $salesOrderPayment
     * @return boolean
     */
    protected function customerUsedStripe(
        \Magento\Sales\Model\Order\Payment $salesOrderPayment
    ){
        if($salesOrderPayment->getMethod() == 'stripe_payments'){
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get Stripe customer id
     *
     * @return int | boolean
     */
    protected function getStripeCustomerId()
    {
        $stripeCustomer = $this->getStripeCustomer();
        return $stripeCustomer->getId();
    }

    /**
     * Get stripe customer object by customer id
     *
     * @return \StripeIntegration\Payments\Model\StripeCustomer
     */
    protected function getStripeCustomer()
    {
        $stripeCustomer = $this->stripeCustomerFactory->create();
        return $stripeCustomer;
    }

    /**
     * Create subscription promos for subscription customer
     *
     * @param \Vonnda\Subscription\Model\SubscriptionCustomer $subscriptionCustomer
     * @return void
     */
    protected function createSubscriptionPromos(
        \Vonnda\Subscription\Model\SubscriptionCustomer $subscriptionCustomer,
        \Vonnda\Subscription\Model\SubscriptionPlan $subscriptionPlan
    ){
        $promoIds = $subscriptionPlan->getDefaultPromoIds();
        if($promoIds){
            $promoIdsArr = explode(',',$promoIds);
            $subscriptionCustomerId = $subscriptionCustomer->getId();
            foreach($promoIdsArr as $ruleId){
                try {
                    $isRuleValid = $this->promoHelper->ruleIdIsValid($ruleId);
                    $couponCode = $this->promoHelper->generateSingleCouponCode($ruleId);
                   
                    $subscriptionPromo = $this->subscriptionPromoFactory->create();
                    $subscriptionPromo->setSubscriptionCustomerId($subscriptionCustomerId)
                                      ->setCouponCode($couponCode);
                    $this->subscriptionPromoRepository->save($subscriptionPromo);
                } catch(\Exception $e){
                    $this->log("Error creating promo code from defaults on front");
                    $this->log($e->getMessage());
                }
            }
        }
        $this->logDebug("Subscription promos processed");
    }

    /**
     * Create subscription device
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Vonnda\Subscription\Model\SubscriptionCustomer $subscriptionCustomer
     * @return \Vonnda\DeviceManager\Api\Data\DeviceManager $subscriptionDevice
     */
    protected function createSubscriptionDevice(
        \Magento\Sales\Model\Order $order,
        \Vonnda\Subscription\Model\SubscriptionCustomer $subscriptionCustomer
    ){
        try {
            $subscriptionPlan = $subscriptionCustomer->getSubscriptionPlan();
            $triggerSku = $subscriptionPlan->getTriggerSku();
            $itemCollection = $order->getItemsCollection();

            $subscriptionDevice = $this->subscriptionDeviceFactory->create();
            $subscriptionDevice->setCustomerId($subscriptionCustomer->getCustomerId())
                ->setPurchaseDate(Carbon::now()->toDateTimeString())
                ->save();

            $subscriptionCustomer->setDeviceId($subscriptionDevice->getEntityId());
            $this->subscriptionCustomerRepository->save($subscriptionCustomer);

            //We first look for matching skus
            foreach($itemCollection as $item){
                $product = $item->getProduct();
                $hasFlag = $product->getData("vonnda_subscription_device_flag");
                $skusEqual = $product->getData("vonnda_subscription_associated_trigger_sku") ==
                            $triggerSku;
                if($hasFlag && $skusEqual){
                    $this->logDebug("Subscription device ID:" . $subscriptionDevice->getId() . " created");
                    $subscriptionDevice->setSku($product->getSku())->save();
                    return;
                }
            }

            //Otherwise we pick the first with the flag
            foreach($itemCollection as $item){
                $product = $item->getProduct();
                $hasFlag = $product->getData("vonnda_subscription_device_flag");
                if($hasFlag){
                    $subscriptionDevice->setSku($product->getSku())->save();
                    $this->logDebug("Subscription device ID:" . $subscriptionDevice->getEntityId() . " created");
                    return;
                }
            }

            if($subscriptionDevice){
                $this->logDebug("Subscription device ID:" . $subscriptionDevice->getEntityId() . " created, sku not set");
            } else {
                $this->logDebug("Subscription device not created");
            }
            return;
        } catch(\Exception $e){
            $this->log("Error associating device on front");
            $this->log($e->getMessage());
        }
    }

    public function log($message)
    {
        return $this->subscriptionLogger->logToSubscriptionManager($message);
    }

    public function logDebug($message)
    {
        if($this->subscriptionHelper->isSubManagerDebugLogEnabled() || self::LOG_DEBUG){
            return $this->subscriptionLogger->logToSubscriptionManager($message);
        }
        return;
    }

    protected function createTealiumAfterPurchaseEvent($subscriptionsCreated, $order)
    {
        try {
            if($subscriptionsCreated > 0){
                $this->tealiumPurchaseEvent->createPurchaseSubdataEvent($order);
            }
        } catch(\Error $e){
            $this->log("Serious error creating tealium after purchase event.");
            $this->log($e->getMessage());
        } catch(\Exception $e){
            $this->log("Error creating tealium after purchase event.");
            $this->log($e->getMessage());
        }
    }

}