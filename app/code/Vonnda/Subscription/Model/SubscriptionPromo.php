<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Vonnda\Subscription\Api\Data\SubscriptionPromoInterface;

class SubscriptionPromo extends AbstractModel implements IdentityInterface, SubscriptionPromoInterface
{
	const SUBSCRIPTION_CUSTOMER_ID = 'subscription_customer_id';
	
	const SUBSCRIPTION_ORDER_ID = 'subscription_order_id';
	
	const USED_STATUS = 'used_status';

	const COUPON_CODE = 'coupon_code';

	const ERROR_MESSAGE = 'error_message';

	const CREATED_AT = 'created_at';

	const USED_AT = 'used_at';

	const CACHE_TAG = 'vonnda_subscription_promo';

	protected $_cacheTag = 'vonnda_subscription_promo';

	protected $_eventPrefix = 'vonnda_subscription_promo';

	protected function _construct()
	{
		$this->_init('Vonnda\Subscription\Model\ResourceModel\SubscriptionPromo');
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
     * @return int $subscriptionCustomerId
     */
	public function getSubscriptionCustomerId()
	{
		return $this->_getData(self::SUBSCRIPTION_CUSTOMER_ID);
	}
 
    /**
     * @param int $subscriptionCustomerId
     * @return $this
     */
	public function setSubscriptionCustomerId($subscriptionCustomerId)
	{
		$this->setData(self::SUBSCRIPTION_CUSTOMER_ID, $subscriptionCustomerId);
		return $this;
	}

	/**
     * @return int $subscriptionOrderId
     */
	public function getSubscriptionOrderId()
	{
		return $this->_getData(self::SUBSCRIPTION_ORDER_ID);
	}
 
    /**
     * @param int $subscriptionOrderId
     * @return $this
     */
	public function setSubscriptionOrderId($subscriptionOrderId)
	{
		$this->setData(self::SUBSCRIPTION_ORDER_ID, $subscriptionOrderId);
		return $this;
	}

    /**
     * @return boolean
     */
	public function getUsedStatus()
	{
		return $this->_getData(self::USED_STATUS);
	}
 
    /**
     * @param boolean $usedStatus
     * @return $this
     */
	public function setUsedStatus($usedStatus)
	{
		$this->setData(self::USED_STATUS, $usedStatus);
		return $this;
	}

	/**
     * @return string
     */
	public function getCouponCode()
	{
		return $this->_getData(self::COUPON_CODE);
	}
 
    /**
     * @param boolean $couponCode
     * @return $this
     */
	public function setCouponCode($couponCode)
	{
		$this->setData(self::COUPON_CODE, $couponCode);
		return $this;
	}

	/**
     * @return string
     */
	public function getErrorMessage()
	{
		return $this->_getData(self::ERROR_MESSAGE);
	}
 
    /**
     * @param boolean $errorMessage
     * @return $this
     */
	public function setErrorMessage($errorMessage)
	{
		$this->setData(self::ERROR_MESSAGE, $errorMessage);
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
	public function getUsedAt()
	{
		return $this->_getData(self::USED_AT);
	}
 
    /**
     * @param string $usedAt
     * @return $this
     */
	public function setUsedAt($usedAt)
	{
		$this->setData(self::USED_AT, $usedAt);
		return $this;
	}

}