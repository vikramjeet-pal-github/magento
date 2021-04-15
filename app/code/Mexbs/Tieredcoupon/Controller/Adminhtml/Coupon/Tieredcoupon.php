<?php
namespace Mexbs\Tieredcoupon\Controller\Adminhtml\Coupon;

abstract class Tieredcoupon extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Mexbs_Tieredcoupon::tieredcoupon';

    /**
     * Initialize requested tiered coupon and put it into registry.
     *
     * @param bool $getRootInstead
     * @return \Mexbs\Tieredcoupon\Model\Tieredcoupon
     */
    protected function _initTieredcoupon()
    {
        $tieredcouponId = (int)$this->getRequest()->getParam('id', false);
        $tieredcoupon = $this->_objectManager->create(\Mexbs\Tieredcoupon\Model\Tieredcoupon::class);

        if ($tieredcouponId) {
            $tieredcoupon->load($tieredcouponId);
        }

        $this->_objectManager->get(\Magento\Framework\Registry::class)->register(\Mexbs\Tieredcoupon\Model\RegistryConstants::CURRENT_COUPON, $tieredcoupon);

        return $tieredcoupon;
    }
}
