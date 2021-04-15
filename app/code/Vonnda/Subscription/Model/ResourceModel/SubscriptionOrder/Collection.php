<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Model\ResourceModel\SubscriptionOrder;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
	protected $_idFieldName = 'id';
	protected $_eventPrefix = 'vonnda_subscription_subscriptionorder_collection';
	protected $_eventObject = 'subscriptionorder_collection';

	/**
	 * Define resource model
	 *
	 * @return void
	 */
	protected function _construct()
	{
		$this->_init('Vonnda\Subscription\Model\SubscriptionOrder', 'Vonnda\Subscription\Model\ResourceModel\SubscriptionOrder');
	}

}