<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container;

class SubscriptionPlan extends Container
{

	protected function _construct()
	{
		$this->_controller = 'adminhtml_subscriptionplan';
		$this->_blockGroup = 'Vonnda_Subscription';
		$this->_headerText = __('Tier');
		$this->_addButtonLabel = __('Create New Tier');
		parent::_construct();
	}
}