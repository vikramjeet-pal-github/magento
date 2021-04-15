<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Vonnda\Subscription\Api\Data\SubscriptionProductInterface;

class SubscriptionProduct extends AbstractModel implements IdentityInterface, SubscriptionProductInterface
{
	const SUBSCRIPTION_PLAN_ID = 'subscription_plan_id';
	
	const PRODUCT_ID = 'product_id';
	
	const QTY = 'qty';

	const PRICE_OVERRIDE = 'price_override';
	
	const CACHE_TAG = 'vonnda_subscription_product';

	protected $_cacheTag = 'vonnda_subscription_product';

	protected $_eventPrefix = 'vonnda_subscription_product';

	protected function _construct()
	{
		$this->_init('Vonnda\Subscription\Model\ResourceModel\SubscriptionProduct');
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
     * @return int $productId
     */
	public function getProductId()
	{
		return $this->_getData(self::PRODUCT_ID);
	}
 
    /**
     * @param int $productId
     * @return $this
     */
	public function setProductId($productId)
	{
		$this->setData(self::PRODUCT_ID, $productId);
		return $this;
	}

    /**
     * @return int
     */
	public function getQty()
	{
		return $this->_getData(self::QTY);
	}
 
    /**
     * @param int $qty
     * @return $this
     */
	public function setQty($qty)
	{
		$this->setData(self::QTY, $qty);
		return $this;
	}

	/**
     * @return decimal
     */
	public function getPriceOverride()
	{
		return $this->_getData(self::PRICE_OVERRIDE);
	}
 
    /**
     * @param string $priceOverride
     * @return $this
     */
	public function setPriceOverride($priceOverride)
	{
		$this->setData(self::PRICE_OVERRIDE, $priceOverride);
		return $this;
	}

}