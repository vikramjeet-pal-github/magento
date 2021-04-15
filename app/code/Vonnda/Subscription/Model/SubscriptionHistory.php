<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Vonnda\Subscription\Api\Data\SubscriptionHistoryInterface;

class SubscriptionHistory extends AbstractModel implements IdentityInterface, SubscriptionHistoryInterface
{
	const SUBSCRIPTION_CUSTOMER_ID = 'subscription_customer_id';

	const CUSTOMER_ID  = 'customer_id';

	const ADMIN_USER_ID = 'admin_user_id';
	
	const BEFORE_SAVE = 'before_save';

	const AFTER_SAVE = 'after_save';
	
	const CACHE_TAG = 'vonnda_subscription_history';

	const CREATED_AT = 'created_at';

	protected $_cacheTag = 'vonnda_subscription_history';

	protected $_eventPrefix = 'vonnda_subscription_history';

	protected function _construct()
	{
		$this->_init('Vonnda\Subscription\Model\ResourceModel\SubscriptionHistory');
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
     * @return int $customerId
     */
	public function getCustomerId()
	{
		return $this->_getData(self::CUSTOMER_ID);
	}
 
    /**
     * @param int $customerId
     * @return $this
     */
	public function setCustomerId($customerId)
	{
		$this->setData(self::CUSTOMER_ID, $customerId);
		return $this;
	}

	/**
     * @return int $adminUserId
     */
	public function getAdminUserId()
	{
		return $this->_getData(self::ADMIN_USER_ID);
	}
 
    /**
     * @param int $adminUserId
     * @return $this
     */
	public function setAdminUserId($adminUserId)
	{
		$this->setData(self::ADMIN_USER_ID, $adminUserId);
		return $this;
	}

    /**
     * @return string
     */
	public function getBeforeSave()
	{
		return $this->_getData(self::BEFORE_SAVE);
	}
 
    /**
     * @param string $beforeSave
     * @return $this
     */
	public function setBeforeSave($beforeSave)
	{
		$this->setData(self::BEFORE_SAVE, $beforeSave);
		return $this;
	}

	/**
     * @return string
     */
	public function getAfterSave()
	{
		return $this->_getData(self::AFTER_SAVE);
	}
 
    /**
     * @param string $afterSave
     * @return $this
     */
	public function setAfterSave($afterSave)
	{
		$this->setData(self::AFTER_SAVE, $afterSave);
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

}