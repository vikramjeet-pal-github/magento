<?php
namespace Mexbs\Tieredcoupon\Block\Adminhtml\Coupon\Grid;

class Container extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_tieredcoupon';
        $this->_headerText = __('Tiered Coupons');
        $this->_addButtonLabel = __('Add New Tiered Coupon');
        parent::_construct();
    }
}
