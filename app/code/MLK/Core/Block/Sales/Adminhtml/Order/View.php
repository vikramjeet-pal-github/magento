<?php
/**
 * @category    Magento
 * @package     Magento_Sales
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MLK\Core\Block\Sales\Adminhtml\Order;

use Magento\Sales\Block\Adminhtml\Order\View as CoreView;

/**
 * Adminhtml sales order view
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class View extends CoreView
{
    
    /**
     * Constructor - OVERRIDE TO REMOVE CREDIT MEMO BUTTON
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _construct()
    {
        parent::_construct();
        $this->removeButton('order_creditmemo');
    }

}