<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Context;

class SubscriptionOrder extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
	
	public function __construct(
		Context $context
	)
	{
		parent::__construct($context);
	}
	
	protected function _construct()
	{
		$this->_init('vonnda_subscription_order', 'id');
	}
	
}