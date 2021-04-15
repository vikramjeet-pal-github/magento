<?php
namespace Mexbs\Tieredcoupon\Controller\Adminhtml\Coupon;

class NewAction extends \Mexbs\Tieredcoupon\Controller\Adminhtml\Coupon\Tieredcoupon
{
    /**
     * New Tiered coupon action
     *
     * @return void
     */
    public function execute()
    {
        $this->_forward('edit');
    }
}
