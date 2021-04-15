<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Carbon\Carbon;
use Magento\Customer\Api\AddressRepositoryInterface;
use Vonnda\Subscription\Api\SubscriptionPlanRepositoryInterface;
use Vonnda\DeviceManager\Api\DeviceManagerRepositoryInterface;
use Vonnda\Subscription\Helper\PromoHelper;

class SubscriptionCustomer extends AbstractModel implements IdentityInterface, SubscriptionCustomerInterface
{

    const CACHE_TAG = 'vonnda_subscription_customer';
	const CUSTOMER_ID = 'customer_id';
	const STATUS = 'status';
	const ERROR_MESSAGE = 'error_message';
	const SHIPPING_ADDRESS_ID = 'shipping_address_id';
	const LAST_ORDER = 'last_order';
	const NEXT_ORDER = 'next_order';
	const CREATED_AT = 'created_at';
	const UPDATED_AT = 'updated_at';
	const SUBSCRIPTION_PLAN_ID = 'subscription_plan_id';
	const DEVICE_ID = 'device_id';
	const SUBSCRIPTION_PAYMENT_ID = 'subscription_payment_id';
	const STATE = 'state';
	const PARENT_ORDER_ID = 'parent_order_id';
	const CANCEL_REASON = 'cancel_reason';
	const END_DATE = 'end_date';
	const SHIPPING_METHOD_OVERWRITE = 'shipping_method_overwrite';
	const SHIPPING_COST_OVERWRITE = 'shipping_cost_overwrite';
	
	//State Codes
	const ERROR_STATE = 'error';
	const ACTIVE_STATE = 'active';
	const INACTIVE_STATE = 'inactive';

	//Status Codes
	const NEW_NO_PAYMENT_STATUS = 'new_no_payment';
	const LEGACY_NO_PAYMENT_STATUS = 'legacy_no_payment';
	const PAYMENT_EXPIRED_STATUS = 'payment_expired';
	const PAYMENT_INVALID_STATUS = 'payment_invalid';
	const ACTIVATE_ELIGIBLE_STATUS = 'activate_eligible';
	const PROCESSING_ERROR_STATUS = 'processing_error';
	const AUTORENEW_OFF_STATUS = 'autorenew_off';
	const AUTORENEW_COMPLETE_STATUS = 'autorenew_complete';
	const AUTORENEW_ON_STATUS = 'autorenew_on';
	const AUTORENEW_FREE_STATUS = 'autorenew_free';
	const RETURNED_STATUS = 'returned';

	protected $_cacheTag = 'vonnda_subscription_customer';
	protected $_eventPrefix = 'vonnda_subscription_customer';
	protected $_customerAddressRepository;
	protected $_subscriptionPlanRepository;
	protected $_deviceRepository;
	protected $_subscriptionPaymentRepository;
	protected $_subscriptionPromoRepository;
	protected $_subscriptionPromoFactory;
	protected $_orderRepository;
	protected $_promoHelper;
	protected $_searchCriteriaBuilder;
    protected $_subscriptionOrderRepository;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
		AddressRepositoryInterface $customerAddressRepository,
		SubscriptionPlanRepositoryInterface $subscriptionPlanRepository,
		DeviceManagerRepositoryInterface $deviceRepository,
		SubscriptionPaymentRepository $subscriptionPaymentRepository,
		SubscriptionPromoRepository $subscriptionPromoRepository,
		SubscriptionPromoFactory $subscriptionPromoFactory,
		PromoHelper $promoHelper,
		SubscriptionOrderRepository $subscriptionOrderRepository,
		OrderRepositoryInterface $orderRepository,
		SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
		$this->_customerAddressRepository = $customerAddressRepository;
		$this->_subscriptionPlanRepository = $subscriptionPlanRepository;
		$this->_deviceRepository = $deviceRepository;
		$this->_subscriptionPaymentRepository = $subscriptionPaymentRepository;
		$this->_subscriptionPromoRepository = $subscriptionPromoRepository;
		$this->_subscriptionPromoFactory = $subscriptionPromoFactory;
		$this->_subscriptionOrderRepository = $subscriptionOrderRepository;
		$this->_promoHelper = $promoHelper;
		$this->_orderRepository = $orderRepository;
		$this->_searchCriteriaBuilder = $searchCriteriaBuilder;
		
		parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

	protected function _construct()
	{
		$this->_init('Vonnda\Subscription\Model\ResourceModel\SubscriptionCustomer');
	}

	public function getIdentities()
	{
		return [self::CACHE_TAG . '_' . $this->getId()];
	}

	public function getDefaultValues()
	{
		return [];
	}

	/**
	 * Maps the status code to the corresponding state
     * @param string $statusCode
     * @return string
     */
    public static function mapStatusCodeToState($statusCode)
    {
	    switch($statusCode) {
		    case self::NEW_NO_PAYMENT_STATUS:
            case self::LEGACY_NO_PAYMENT_STATUS:
            case self::ACTIVATE_ELIGIBLE_STATUS:
            case self::AUTORENEW_OFF_STATUS:
            case self::AUTORENEW_COMPLETE_STATUS:
            case self::RETURNED_STATUS:
			    return self::INACTIVE_STATE;
		    case self::PAYMENT_EXPIRED_STATUS:
            case self::PAYMENT_INVALID_STATUS:
		    case self::PROCESSING_ERROR_STATUS:
			    return self::ERROR_STATE;
		    case self::AUTORENEW_ON_STATUS:
		    case self::AUTORENEW_FREE_STATUS:
				return self::ACTIVE_STATE;
		    default:
			    throw new \Exception("Status code could not be mapped to state");
	    }
    }
 
    /**
     * {@inheritdoc}
     */
	public function getCustomerId()
	{
		return $this->_getData(self::CUSTOMER_ID);
	}
 
    /**
     * {@inheritdoc}
     */
	public function setCustomerId($customerId)
	{
		$this->setData(self::CUSTOMER_ID, $customerId);
		return $this;
	}

    /**
     * {@inheritdoc}
     */
	public function getStatus()
	{
		return $this->_getData(self::STATUS);
	}
 
    /**
     * {@inheritdoc}
     */
	public function setStatus($status)
	{
		//if this throws due to invalid status we want it to happen before status is set
		$this->setData(self::STATE, self::mapStatusCodeToState($status));
		$this->setData(self::STATUS, $status);
		if($status === self::AUTORENEW_ON_STATUS){
			$this->setData(self::END_DATE, null);
		}
		return $this;
	}

	/**
     * @return int $shippingAddressId
     */
	public function getShippingAddressId()
	{
		return $this->_getData(self::SHIPPING_ADDRESS_ID);
	}
 
    /**
     * @param int $shippingAddressId
     * @return $this
     */
	public function setShippingAddressId($shippingAddressId)
	{
		$this->setData(self::SHIPPING_ADDRESS_ID, $shippingAddressId);
		return $this;
	}

	/**
     * @return int $subscriptionPlanId
     */
	public function getSubscriptionPlanId()
	{
		return $this->_getData(self::SUBSCRIPTION_PLAN_ID);
	}
 
    /**
     * @param int $subscriptionPlanId
     * @return $this
     */
	public function setSubscriptionPlanId($subscriptionPlanId)
	{
		$this->setData(self::SUBSCRIPTION_PLAN_ID, $subscriptionPlanId);
		return $this;
	}

	/**
     * @return int $deviceId
     */
	public function getDeviceId()
	{
		return $this->_getData(self::DEVICE_ID);
	}
 
    /**
     * @param int $deviceId
     * @return $this
     */
	public function setDeviceId($deviceId)
	{
		$this->setData(self::DEVICE_ID, $deviceId);
		return $this;
	}

	/**
     * @return int $subscriptionPaymentId
     */
    public function getSubscriptionPaymentId()
    {
	    return $this->_getData(self::SUBSCRIPTION_PAYMENT_ID);
    }

   /**
    * @param int $subscriptionPaymentId
    * @return $this
    */
    public function setSubscriptionPaymentId($subscriptionPaymentId)
    {
	    $this->setData(self::SUBSCRIPTION_PAYMENT_ID, $subscriptionPaymentId);
	    return $this;
	}
	
	/**
     * @return int $parentOrderId
     */
    public function getParentOrderId()
    {
	    return $this->_getData(self::PARENT_ORDER_ID);
    }

   /**
    * @param int $parentOrderId
    * @return $this
    */
    public function setParentOrderId($parentOrderId)
    {
	    $this->setData(self::PARENT_ORDER_ID, $parentOrderId);
	    return $this;
    }

   	/**
     * {@inheritdoc}
     */
	public function getNextOrder()
	{
		return $this->_getData(self::NEXT_ORDER);
	}
 
    /**
     * {@inheritdoc}
     */
	public function setNextOrder($nextOrder)
	{
		$this->setData(self::NEXT_ORDER, $nextOrder);
		return $this;
	}

	/**
     * {@inheritdoc}
     */
	public function getLastOrder()
	{
		return $this->_getData(self::LAST_ORDER);
	}
 
	/**
     * {@inheritdoc}
     */
	public function setLastOrder($lastOrder)
	{
		$this->setData(self::LAST_ORDER, $lastOrder);
		return $this;
	}

	/**
     * {@inheritdoc}
     */
	public function getCreatedAt()
	{
		return $this->_getData(self::CREATED_AT);
	}
 
	/**
     * {@inheritdoc}
     */
	public function setCreatedAt($createdAt)
	{
		$this->setData(self::CREATED_AT, $createdAt);
		return $this;
	}

	/**
     * {@inheritdoc}
     */
	public function getUpdatedAt()
	{
		return $this->_getData(self::UPDATED_AT);
	}
 
	/**
     * {@inheritdoc}
     */
	public function setUpdatedAt($updatedAt)
	{
		$this->setData(self::UPDATED_AT, $updatedAt);
		return $this;
	}

	/**
     * @param string $endDate
	 * return $this
     */
	public function getEndDate()
	{
		return $this->_getData(self::END_DATE);
	}

	/**
     * @param string $endDate
	 * return $this
     */
	public function setEndDate($endDate)
	{
		$this->setData(self::END_DATE, $endDate);
		return $this;
	}

	/**
     * {@inheritdoc}
     */

	public function getErrorMessage()
	{
		return $this->_getData(self::ERROR_MESSAGE);
	}
 
    /**
     * @param string $errorMessage
     * @return $this
     */
	public function setErrorMessage($errorMessage)
	{
		$this->setData(self::ERROR_MESSAGE, $errorMessage);
		return $this;
	}

	/**
     * {@inheritdoc}
     */

    public function getState()
    {
	    return $this->_getData(self::STATE);
    }

   /**
    * @param string $state
    * @return $this
    */
    public function setState($state)
    {
	    $this->setData(self::STATE, $state);
	    return $this;
    }

	/**
     * {@inheritdoc}
     */
	public function getShippingAddress()
	{
		$shippingAddressId = $this->_getData(self::SHIPPING_ADDRESS_ID);
		if(isset($shippingAddressId)){
			return $this->_customerAddressRepository->getById($shippingAddressId);
		}
		return null;
	}
 
    /**
     * {@inheritdoc}
     */
	public function setShippingAddress($shippingAddress)
	{
		if(is_int($shippingAddress)){
			$shippingAddressId = $shippingAddress;
		} else {
			$shippingAddressId = $shippingAddress->getId();
		}

		if(!$shippingAddressId){
			$this->setData(self::SHIPPING_ADDRESS_ID, null);
			return $this;
		} 

		try {
			$shippingAddress = $this->_customerAddressRepository->getById($shippingAddressId);
			$this->setData(self::SHIPPING_ADDRESS_ID, $shippingAddress->getId());
			return $this;
		} catch(\Exception $e){
			throw new \Exception('Invalid Shipping Address');
			return $this;
		}
	}

	/**
     * {@inheritdoc}
     */
	public function getSubscriptionPlan()
	{
		$planId = $this->_getData(self::SUBSCRIPTION_PLAN_ID);
		if(isset($planId)){
			return $this->_subscriptionPlanRepository->getById($planId);
		}
		return null;
	}
 
	/**
     * {@inheritdoc}
     */
	public function setSubscriptionPlan($subscriptionPlan)
	{
		if(is_int($subscriptionPlan)){
			$subscriptionPlanId = $subscriptionPlan;
		} else {
			$subscriptionPlanId = $subscriptionPlan->getId();
		}
		$subscriptionPlan = $this->_subscriptionPlanRepository->getById($subscriptionPlanId);
		if(isset($subscriptionPlan)){
			$this->setData(self::SUBSCRIPTION_PLAN_ID, $subscriptionPlan->getId());
		}
		return $this;
	}

	/**
     * {@inheritdoc}
     */
	public function getDevice()
	{
		$deviceId = $this->_getData(self::DEVICE_ID);
		if(isset($deviceId)){
			return $this->_deviceRepository->getById($deviceId);
		}
		return null;
	}
 
    /**
     * {@inheritdoc}
     */
	public function setDevice($device)
	{	
		$device = $this->_deviceRepository->save($device);
		if($deviceId = $device->getEntityId()){
			$this->setDeviceId($deviceId);
		}
		return $this;
	}

	/**
     * {@inheritdoc}
     */
	public function getPayment()
	{
		if($this->getSubscriptionPaymentId()){
			return  $this->_subscriptionPaymentRepository
				        ->getById($this->getSubscriptionPaymentId());
		}
		return null;
	}

	/**
     * {@inheritdoc}
     */
     public function setPayment($subscriptionPayment)
     {
		if(!$subscriptionPayment->getStatus()){
			throw new \Exception("Status cannot be null");
		}
		$subscriptionPayment = $this->_subscriptionPaymentRepository->save($subscriptionPayment);
		if($subscriptionPaymentId = $subscriptionPayment->getId()){
			$this->setSubscriptionPaymentId($subscriptionPaymentId);
			return $this;
		}
		return $this;
     }
 
	/**
     * {@inheritdoc}
     */
	public function getPromos()
	{
		$subscriptionPromos = [];
		if($this->getId()){
			$subscriptionPromoList = $this->_subscriptionPromoRepository->getListBySubscriptionCustomerId($this->getId());
			foreach($subscriptionPromoList->getItems() as $item){
				$subscriptionPromos[] = $item;
			}
		}
		return $subscriptionPromos;
	}

	/**
     * {@inheritdoc}
     */
    public function setPromos($promos)
    {
	    return $this;
    }

	/**
     * {@inheritdoc}
     */
    public function getCouponCodes()
    {
	    return [];
    }

	/**
     * {@inheritdoc}
     */
	public function setCouponCodes($couponCodes)
	{                
		foreach($couponCodes as $couponCode){
			try {
				if($this->_promoHelper->couponCodeIsValid($couponCode)){
					$subscriptionPromo = $this->_subscriptionPromoFactory->create();
					$subscriptionPromo->setSubscriptionCustomerId($this->getId())
						->setCouponCode($couponCode);
					$this->_subscriptionPromoRepository->save($subscriptionPromo);	
				}
			} catch(\Exception $e){

			}
		}
		return $this;
	}

	/**
     * {@inheritdoc}
     */
    public function getCancelReason()
    {
	    return $this->_getData(self::CANCEL_REASON);
    }

   /**
    * @param string $cancelReason
    * @return $this
    */
    public function setCancelReason($cancelReason)
    {
	    $this->setData(self::CANCEL_REASON, $cancelReason);
	    return $this;
	}

	/**
     * @return string
     */
    public function getShippingMethodOverwrite()
    {
	    return $this->_getData(self::SHIPPING_METHOD_OVERWRITE);
    }

   /**
    * @param string $shippingMethodOverwrite
    * @return $this
    */
    public function setShippingMethodOverwrite($shippingMethodOverwrite)
    {
	    $this->setData(self::SHIPPING_METHOD_OVERWRITE, $shippingMethodOverwrite);
	    return $this;
	}

	/**
     * @return float
     */
    public function getShippingCostOverwrite()
    {
	    return $this->_getData(self::SHIPPING_COST_OVERWRITE);
    }

   /**
    * @param string $shippingCostOverwrite
    * @return $this
    */
    public function setShippingCostOverwrite($shippingCostOverwrite)
    {
	    $this->setData(self::SHIPPING_COST_OVERWRITE, $shippingCostOverwrite);
	    return $this;
	}

	/**
	 *  Because null means null
     * @param string $shippingCostOverwrite
     * @return $this
     */
	public function setShippingCostOverwriteToNull()
	{
		$resourceModel = $this->getResource();
		$resourceModel->setShippingCostOverwriteToNull($this->getId());
		return $this;
	}
	
	/**
	 * @param void
     * @return $this
     */
	public function isNextOrderDateInPast(){
        try {
            $nextOrderDate = Carbon::createFromTimeString($this->getNextOrder());
            $today = Carbon::today();

            if($today > $nextOrderDate){
                return true;
            }

            return false;
        } catch(\Exception $e){
			$this->logger->info($e->getMessage());
		}
		
		return false;
    }
	
	/**
     * {@inheritdoc}
     */
	public function setRenewalDate($renewalDate)
	{
		return true;
	}

	/**
     * {@inheritdoc}
     */
    public function getRenewalDate()
    {
        try {
			$renewalDate = $this->getRenewalDateObject();
			if(!$renewalDate){
				return null;
			}

			return $renewalDate->format("m/d/Y");
        } catch(\Exception $e){
            return null;
        }
	}

	/**
     * {@inheritdoc}
     */
    public function getRenewalDateObject()
    {
        try {
			if(!$this->getNextOrder()){
				return null;
			}
			$subscriptionPlan = $this->getSubscriptionPlan();
			$nextOrderDate = Carbon::createFromTimeString($this->getNextOrder())->setTimezone('America/Los_Angeles');

			$isAutoRenewOnOrFree = $this->getStatus() === self::AUTORENEW_ON_STATUS || 
				$this->getStatus() === self::AUTORENEW_FREE_STATUS;
			
			if(!$isAutoRenewOnOrFree){
				return $nextOrderDate;
			}
			
			if($subscriptionPlan->getNumberOfFreeShipments()){
				$freeOrdersLeft = $subscriptionPlan->getNumberOfFreeShipments() - $this->countSuccessFullSubscriptionOrders();
				if($freeOrdersLeft > 0){
					for($i = 0; $i < $freeOrdersLeft; $i++){
						$nextOrderDate->add($subscriptionPlan->getFrequency(), $subscriptionPlan->getFrequencyUnits());
					}
				}
			}

            return $nextOrderDate;
        } catch(\Exception $e){
            return null;
        }
	}


	/**
	 * @param void
     * @return $this
     */
	public function renewalDateInPast()
	{
		if($this->getRenewalDateObject() < Carbon::now('America/Los_Angeles')){
			return true;
		}

		return false;
	}
	
	/**
	 * @param void
     * @return $this
     */
	public function isSubscriptionExpired(){
		$subscriptionPlan = $this->getSubscriptionPlan();

		if($this->getStatus() === self::AUTORENEW_COMPLETE_STATUS){
            return true;
        }
        
        if(($this->getStatus() === self::AUTORENEW_OFF_STATUS || 
            $this->getStatus() === self::LEGACY_NO_PAYMENT_STATUS ||
            $this->getStatus() === self::NEW_NO_PAYMENT_STATUS)
            && $this->renewalDateInPast()){
                return true;
            }
        
        if($this->getEndDate()){
            $now = Carbon::now();
            $endDate = Carbon::createFromTimeString($this->getEndDate());
            if($endDate->lessThan($now)){
                return true;
            }
        }
        
        if(!$this->getDuration()){
            return false;
        }
        
        $numSubscriptionOrders = $this->countSuccessFullSubscriptionOrders();
        $subscriptionHasOrdersLeft = $numSubscriptionOrders < $subscriptionPlan->getDuration();
		if($subscriptionHasOrdersLeft){
			return false;
		} else {
			return true;
		}

	}

	protected function getNumOfSubscriptionOrdersLeft(){
		$subscriptionPlan = $this->getSubscriptionPlan();
		$numSubscriptionOrders = $this->countSuccessFullSubscriptionOrders();
		if($numSubscriptionOrders == 0){
			return $subscriptionPlan->getDuration();
		}
		if($numSubscriptionOrders >= $subscriptionPlan->getDuration()){
			return 0;
		}
		return $subscriptionPlan->getDuration() - $numSubscriptionOrders;
	}
	
    public function countSuccessFullSubscriptionOrders(){
        $searchCriteria = $this->_searchCriteriaBuilder
			->addFilter('status', 'success', 'eq')
			->addFilter('subscription_customer_id', $this->getId(), 'eq')
			->create();
        $subscriptionOrderList = $this->_subscriptionOrderRepository->getList($searchCriteria);
        return $subscriptionOrderList->getTotalCount();
	}

	public function hasFreeShipmentsLeft(){
		$subscriptionPlan = $this->getSubscriptionPlan();
		if($subscriptionPlan->getNumberOfFreeShipments()){
			$freeOrdersLeft = $subscriptionPlan->getNumberOfFreeShipments() - $this->countSuccessFullSubscriptionOrders();
			if($freeOrdersLeft > 0){
				return true;
			}
		}
	}

	/**
     * {@inheritdoc}
     */
    public function getWeekOfString()
    {
        $isExpired = $this->isSubscriptionExpired();
        $isInactive = $this->getState() === self::INACTIVE_STATE;
        $isErrorState = $this->getState() === self::ERROR_STATE;

        if($isExpired || $isInactive || $isErrorState){
            return "None scheduled";
        }
        if($this->getNextOrder()){
            $nextOrderDate = Carbon::createFromTimeString($this->getNextOrder())->setTimezone('America/Los_Angeles');
            $nextWeek = array(0,5,6);
            if(in_array($nextOrderDate->dayOfWeek, $nextWeek)) {
                $week = $nextOrderDate->addWeeks(1)->startOfWeek();
            } else {
                $week = $nextOrderDate->startOfWeek();
            }
            return 'Week of '.$week->format("m/d/Y");
        }

        return "None scheduled";
	}
	
	/**
     * {@inheritdoc}
     */
	public function setWeekOfString($weekOfString)
    {
        return true;
    }
	
 }