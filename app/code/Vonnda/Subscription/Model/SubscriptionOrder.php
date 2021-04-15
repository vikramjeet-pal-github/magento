<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Vonnda\Subscription\Api\Data\SubscriptionOrderInterface;

class SubscriptionOrder extends AbstractModel implements IdentityInterface, SubscriptionOrderInterface
{
	const SUBSCRIPTION_CUSTOMER_ID = 'subscription_customer_id';
	
	const ORDER_ID = 'order_id';
	
	const STATUS = 'status';
	
	const ERROR_MESSAGE = 'error_message';
	
	const CREATED_AT = 'created_at';
	
	const UPDATED_AT = 'updated_at';

	//Status Constants
	const SUCCESS_STATUS = 'success';
	const ERROR_STATUS = 'error';
	
	const CACHE_TAG = 'vonnda_subscription_order';

	protected $_cacheTag = 'vonnda_subscription_order';

	protected $_eventPrefix = 'vonnda_subscription_order';

	protected function _construct()
	{
		$this->_init('Vonnda\Subscription\Model\ResourceModel\SubscriptionOrder');
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
     * @return int $customerSubscriptionId
     */
	public function getCustomerSubscriptionId()
	{
		return $this->_getData(self::SUBSCRIPTION_CUSTOMER_ID);
	}
 
    /**
     * @param int $customerSubscriptionId
     * @return $this
     */
	public function setCustomerSubscriptionId($customerSubscriptionId)
	{
		$this->setData(self::SUBSCRIPTION_CUSTOMER_ID, $customerSubscriptionId);
		return $this;
	}

    /**
     * @return int $orderId
     */
	public function getOrderId()
	{
		return $this->_getData(self::ORDER_ID);
	}
 
    /**
     * @param int $orderId
     * @return $this
     */
	public function setOrderId($orderId)
	{
		$this->setData(self::ORDER_ID, $orderId);
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
     * @return string
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
}