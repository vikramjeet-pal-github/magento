<?php

namespace MLK\Core\Block\Paypal\Adminhtml\Order;

use Magento\Paypal\Block\Adminhtml\Order\View as CorePaypalView;

/**
 * Adminhtml sales order view - OVERRIDEN BECAUSE IT ALSO RECONSTRUCTS THE MENUBAR
 * @api
 * @since 100.2.2
 */
class View extends CorePaypalView
{
    
    protected function _construct()
    {
        parent::_construct();
        $this->removeButton('order_creditmemo');
    }

}
