<?php
namespace Mexbs\Tieredcoupon\Block\Adminhtml\Coupon;

class SubCoupons extends \Magento\Backend\Block\Template
{
    /**
     * Block template
     *
     * @var string
     */
    protected $_template = 'Mexbs_Tieredcoupon::coupon/edit/sub_coupons.phtml';

    /**
     * @var \Mexbs\Tieredcoupon\Block\Adminhtml\Coupon\SubCoupons\Grid
     */
    protected $blockGrid;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * AssignProducts constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->jsonEncoder = $jsonEncoder;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve instance of grid block
     *
     * @return \Magento\Framework\View\Element\BlockInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getBlockGrid()
    {
        if (null === $this->blockGrid) {
            $this->blockGrid = $this->getLayout()->createBlock(
                \Mexbs\Tieredcoupon\Block\Adminhtml\Coupon\SubCoupons\Grid::class,
            'coupon.sub_coupon.grid'
            );
        }
        return $this->blockGrid;
    }

    /**
     * Return HTML of grid block
     *
     * @return string
     */
    public function getGridHtml()
    {
        return $this->getBlockGrid()->toHtml();
    }

    /**
     * @return string
     */
    public function getSubCouponsJson()
    {
        $subCoupons = $this->getTieredcoupon()->getSubCouponIds();
        if (!empty($subCoupons)) {
            $subCouponsKeyAsValue = [];
            foreach($subCoupons as $subCouponId){
                $subCouponsKeyAsValue[$subCouponId] = $subCouponId;
            }
            return $this->jsonEncoder->encode($subCouponsKeyAsValue);
        }
        return '{}';
    }

    /**
     * Retrieve current category instance
     *
     * @return \Mexbs\Tieredcoupon\Model\Tieredcoupon
     */
    public function getTieredcoupon()
    {
        return $this->registry->registry(\Mexbs\Tieredcoupon\Model\RegistryConstants::CURRENT_COUPON);
    }
}
