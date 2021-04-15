<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;

use Vonnda\Subscription\Api\Data\SubscriptionPlanInterface;
use Vonnda\Subscription\Model\SubscriptionProductRepository;


class SubscriptionPlan extends AbstractModel implements IdentityInterface, SubscriptionPlanInterface
{

    const TITLE = 'title';
    
    const SHORT_DESCRIPTION = 'short_description';
    
    const LONG_DESCRIPTION = 'long_description';
    
    const MORE_INFO = 'more_info';
    
    const FREQUENCY_UNITS = 'frequency_units';
    
    const FREQUENCY = 'frequency';
    
    const TRIGGER_SKU = 'trigger_sku';
    
    const SORT_ORDER = 'sort_order';
    
    const DURATION = 'duration';
    
    const VISIBLE = 'visible';
	
	const STATUS = 'status';
		
	const CREATED_AT = 'created_at';
	
	const UPDATED_AT = 'updated_at';

	const DEFAULT_PROMO_IDS = 'default_promo_ids';

	const NUMBER_OF_FREE_SHIPMENTS = 'number_of_free_shipments';

	const DEVICE_SKU = 'device_sku';
	
	const PAYMENT_REQUIRED_FOR_FREE = 'payment_required_for_free';

	const IDENTIFIER = 'identifier';
	
	const STORE_ID = 'store_id';

	const FALLBACK_PLAN = 'fallback_plan';

	//Status codes
	const ACTIVE_STATUS = 'active';
	const INACTIVE_STATUS = 'inactive';

	// Legacy US Identifiers
	const LEGACY_PLAN_4950_CODE = "mh1-sub-legacy-4950";
	const LEGACY_PLAN_6450_CODE = "mh1-sub-legacy-6450";
	const LEGACY_PLAN_6500_CODE = "mh1-sub-legacy-6500";
	const LEGACY_PLAN_6450_PAYMENT_REQ_CODE = "mh1-sub-legacy-6450-payment-required";
	const LEGACY_PLAN_6500_PAYMENT_REQ_CODE = "mh1-sub-legacy-6500-payment-required";
	const LEGACY_PLAN_4950_FREE_ELIGIBLE_CODE = "mh1-sub-legacy-12-4950-free-eligible";
	const LEGACY_PLAN_6450_FREE_ELIGIBLE_CODE = "mh1-sub-legacy-12-6450-free-eligible";
	const LEGACY_PLAN_4950_FREE_RECEIVED_CODE = "mh1-sub-legacy-12-4950-free-received";
	const LEGACY_PLAN_6450_FREE_RECEIVED_CODE = "mh1-sub-legacy-12-6450-free-received";     

	// Legacy Canada Identifiers
	const LEGACY_PLAN_CANADA = "mh1-basic-ca";

	const CACHE_TAG = 'vonnda_subscription_plan';

	protected $_cacheTag = 'vonnda_subscription_plan';

	protected $_eventPrefix = 'vonnda_subscription_plan';

	protected $subscriptionProductRepository;

	protected $productRepository;

	public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
		array $data = [],
		ProductRepositoryInterface $productRepository,
		SubscriptionProductRepository $subscriptionProductRepository
    ){
		$this->productRepository = $productRepository;
		$this->subscriptionProductRepository = $subscriptionProductRepository;
		
		parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

	protected function _construct()
	{
		$this->_init('Vonnda\Subscription\Model\ResourceModel\SubscriptionPlan');
	}

	public function getIdentities()
	{
		return [self::CACHE_TAG . '_' . $this->getId()];
	}

	public function getDefaultValues()
	{
		$values = [];

		return $values;
	}
 
    /**
     * @return string
     */
	public function getTitle()
	{
		return $this->_getData(self::TITLE);
	}
 
    /**
     * @param string $title
     * @return $this
     */
	public function setTitle($title)
	{
		$this->setData(self::TITLE, $title);
		return $this;
    }
    
    /**
     * @return string
     */
	public function getShortDescription()
	{
		return $this->_getData(self::SHORT_DESCRIPTION);
	}
 
    /**
     * @param string $shortDescription
     * @return $this
     */
	public function setShortDescription($shortDescription)
	{
		$this->setData(self::SHORT_DESCRIPTION, $shortDescription);
		return $this;
    }
    
    /**
     * @return string
     */
	public function getLongDescription()
	{
		return $this->_getData(self::LONG_DESCRIPTION);
	}
 
    /**
     * @param string $longDescription
     * @return $this
     */
	public function setLongDescription($longDescription)
	{
		$this->setData(self::LONG_DESCRIPTION, $longDescription);
		return $this;
    }

    /**
     * @return string
     */
	public function getMoreInfo()
	{
		return $this->_getData(self::MORE_INFO);
	}
 
    /**
     * @param string $moreInfo
     * @return $this
     */
	public function setMoreInfo($moreInfo)
	{
		$this->setData(self::MORE_INFO, $moreInfo);
		return $this;
    }

    /**
     * @return string
     */
	public function getFrequencyUnits()
	{
		return $this->_getData(self::FREQUENCY_UNITS);
	}
 
    /**
     * @param string $frequencyUnits
     * @return $this
     */
	public function setFrequencyUnits($frequencyUnits)
	{
		$this->setData(self::FREQUENCY_UNITS, $frequencyUnits);
		return $this;
    }

    /**
     * @return string
     */
	public function getFrequency()
	{
		return $this->_getData(self::FREQUENCY);
	}
 
    /**
     * @param string $frequency
     * @return $this
     */
	public function setFrequency($frequency)
	{
		$this->setData(self::FREQUENCY, $frequency);
		return $this;
    }

    /**
     * @return string
     */
	public function getTriggerSku()
	{
		return $this->_getData(self::TRIGGER_SKU);
	}
 
    /**
     * @param string $triggerSku
     * @return $this
     */
	public function setTriggerSku($triggerSku)
	{
		$this->setData(self::TRIGGER_SKU, $triggerSku);
		return $this;
	}
	
    /**
     * @return string
     */
	public function getSortOrder()
	{
		return $this->_getData(self::SORT_ORDER);
	}
 
    /**
     * @param string $sortOrder
     * @return $this
     */
	public function setSortOrder($sortOrder)
	{
		$this->setData(self::SORT_ORDER, $sortOrder);
		return $this;
    }
    
    /**
     * @return string
     */
	public function getDuration()
	{
		return $this->_getData(self::DURATION);
	}
 
    /**
     * @param string $duration
     * @return $this
     */
	public function setDuration($duration)
	{
		$this->setData(self::DURATION, $duration);
		return $this;
	}

    /**
     * @return string
     */
	public function getStatus()
	{
		return $this->_getData(self::STATUS);
	}
 
    /**
     * @param string $status
     * @return $this
     */
	public function setStatus($status)
	{
		$this->setData(self::STATUS, $status);
		return $this;
    }
    
    /**
     * @return boolean
     */
	public function getVisible()
	{
		return $this->_getData(self::VISIBLE);
	}
 
    /**
     * @param boolean $visible
     * @return $this
     */
	public function setVisible($visible)
	{
		$this->setData(self::VISIBLE, $visible);
		return $this;
	}

	/**
     * @return int|null
     */
	public function getStoreId()
	{
		return $this->_getData(self::STORE_ID);
	}
 
    /**
     * @param int|null $storeId
     * @return $this
     */
	public function setStoreId($storeId)
	{
		$this->setData(self::STORE_ID, $storeId);
		return $this;
	}

    /**
     * @return string
     */
	public function getCreatedAt()
	{
		return $this->_getData(self::CREATED_AT);
	}
 
    /**
     * @param string $createdAt
     * @return $this
     */
	public function setCreatedAt($createdAt)
	{
		$this->setData(self::CREATED_AT, $createdAt);
		return $this;
	}

    /**
     * @return string
     */
	public function getUpdatedAt()
	{
		return $this->_getData(self::UPDATED_AT);
	}
 
    /**
     * @param string $updatedAt
     * @return $this
     */
	public function setUpdatedAt($updatedAt)
	{
		$this->setData(self::UPDATED_AT, $updatedAt);
		return $this;
	}

	/**
     * @return string
     */
	public function getDefaultPromoIds()
	{
		return $this->_getData(self::DEFAULT_PROMO_IDS);
	}
 
    /**
     * @param string $defaultPromoIds
     * @return $this
     */
	public function setDefaultPromoIds($defaultPromoIds)
	{
		$this->setData(self::DEFAULT_PROMO_IDS, $defaultPromoIds);
		return $this;
	}

	/**
     * @return int
     */
	public function getNumberOfFreeShipments()
	{
		return $this->_getData(self::NUMBER_OF_FREE_SHIPMENTS);
	}
 
    /**
     * @param string $numberOfFreeShipments
     * @return $this
     */
	public function setNumberOfFreeShipments($numberOfFreeShipments)
	{
		$this->setData(self::NUMBER_OF_FREE_SHIPMENTS, $numberOfFreeShipments);
		return $this;
	}

	public function getDeviceSku()
    {
        return $this->_getData(self::DEVICE_SKU);
    }

    public function setDeviceSku($deviceSku)
    {
        $this->setData(self::DEVICE_SKU, $deviceSku);
        return $this;
	}
	
	public function getPaymentRequiredForFree()
    {
        return $this->_getData(self::PAYMENT_REQUIRED_FOR_FREE);
    }

    public function setPaymentRequiredForFree($required)
    {
        $this->setData(self::PAYMENT_REQUIRED_FOR_FREE, $required);
        return $this;
	}
	
	public function getIdentifier()
    {
        return $this->_getData(self::IDENTIFIER);
    }

    public function setIdentifier($identifier)
    {
        $this->setData(self::IDENTIFIER, $identifier);
        return $this;
	}

	/**
     * @return string
     */
	public function getFallbackPlan()
    {
        return $this->_getData(self::FALLBACK_PLAN);
    }

	/**
     * @param string $fallbackPlan
     * @return $this
     */
    public function setFallbackPlan($fallbackPlan)
    {
        $this->setData(self::FALLBACK_PLAN, $fallbackPlan);
		return $this;
	}
	
	/**
     * @param void
     * @return float $total
     * {@inheritdoc}
     */
	public function getPlanPrice()
	{
		$total = 0.00;
		$subscriptionProducts = $this->subscriptionProductRepository->getSubscriptionProductsByPlanId($this->getId());
        foreach ($subscriptionProducts->getItems() as $subscriptionProduct) {
            $product = $this->productRepository->getById($subscriptionProduct->getProductId());
            $priceOverride = $subscriptionProduct->getPriceOverride();
            if ($priceOverride) {
                $total += $priceOverride;
            } else {
				$total += $product->getPrice();
			}
		}
		
		return $total;
	}

	/**
     * {@inheritdoc}
     */
	public function setPlanPrice($planPrice)
	{
		return true;
	}
	
}