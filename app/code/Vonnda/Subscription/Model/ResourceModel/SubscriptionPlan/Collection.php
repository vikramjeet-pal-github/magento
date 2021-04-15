<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Model\ResourceModel\SubscriptionPlan;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
	protected $_idFieldName = 'id';
	protected $_eventPrefix = 'vonnda_subscription_subscriptionplan_collection';
	protected $_eventObject = 'subscriptionplan_collection';

	/**
	 * Define resource model
	 *
	 * @return void
	 */
	protected function _construct()
	{
		$this->_init('Vonnda\Subscription\Model\SubscriptionPlan', 'Vonnda\Subscription\Model\ResourceModel\SubscriptionPlan');
	}

}