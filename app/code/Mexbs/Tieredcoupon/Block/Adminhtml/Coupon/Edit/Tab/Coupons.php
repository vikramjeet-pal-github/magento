<?php
namespace Mexbs\Tieredcoupon\Block\Adminhtml\Coupon\Edit\Tab;

class Coupons extends \Magento\Framework\View\Element\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    protected $_coreRegistry = null;


    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Auto-generate Coupon Codes');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Auto-generate Coupon Codes');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function setCanSHow($canShow)
    {
        $this->_data['config']['canShow'] = $canShow;
    }

    public function getIsTieredCouponNew(){
        $tieredCoupon = $this->_coreRegistry->registry(\Mexbs\Tieredcoupon\Model\RegistryConstants::CURRENT_COUPON);
        return (!$tieredCoupon || !$tieredCoupon->getId());
    }
}
