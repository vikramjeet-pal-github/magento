<?php
namespace Mexbs\Tieredcoupon\Block\Adminhtml\Coupon\SubCoupons;

use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Block\Widget\Grid\Extended;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Coupon\CollectionFactory
     */
    protected $_salesRuleCouponCollectionFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\SalesRule\Model\ResourceModel\Coupon\CollectionFactory $salesRuleCouponCollectionFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\SalesRule\Model\ResourceModel\Coupon\CollectionFactory $salesRuleCouponCollectionFactory,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        $this->_salesRuleCouponCollectionFactory = $salesRuleCouponCollectionFactory;
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('tieredcoupon_sub_coupons');
        $this->setDefaultSort('tieredcoupon_coupon_id');
        $this->setUseAjax(true);
    }

    /**
     * @return \Mexbs\Tieredcoupon\Model\Tieredcoupon
     */
    public function getTieredcoupon()
    {
        return $this->_coreRegistry->registry(\Mexbs\Tieredcoupon\Model\RegistryConstants::CURRENT_COUPON);
    }

    /**
     * @param Column $column
     * @return $this
     */
    protected function _addColumnFilterToCollection($column)
    {
        // Set custom filter for in category flag
        if ($column->getId() == 'is_subcoupon') {
            $subCouponIds = $this->_getSelectedSubCouponIds();
            if (empty($subCouponIds)) {
                $subCouponIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('coupon_id', ['in' => $subCouponIds]);
            } elseif (!empty($subCouponIds)) {
                $this->getCollection()->addFieldToFilter('coupon_id', ['nin' => $subCouponIds]);
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }

    /**
     * @return \Magento\Backend\Block\Widget\Grid
     */
    protected function _prepareCollection()
    {
        if ($this->getTieredcoupon()->getId()) {
            $this->setDefaultFilter(['is_subcoupon' => 1]);
        }
        /**
         * @var \Magento\SalesRule\Model\ResourceModel\Coupon\Collection $collection
         */
        $collection = $this->_salesRuleCouponCollectionFactory->create();

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * @return Extended
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'is_subcoupon',
            [
                'type' => 'checkbox',
                'name' => 'is_subcoupon',
                'values' => $this->_getSelectedSubCouponIds(),
                'index' => 'coupon_id',
                'field_name' => 'is_subcoupon',
                'header_css_class' => 'col-select col-massaction',
                'column_css_class' => 'col-select col-massaction'
            ]
        );
        $this->addColumn(
            'coupon_id',
            [
                'header' => __('ID'),
                'sortable' => true,
                'index' => 'coupon_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );
        $this->addColumn('sub_coupon_code', ['header' => __('Code'), 'index' => 'code']);

        return parent::_prepareColumns();
    }

    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('tieredcoupon/coupon/subcoupons', ['_current' => true]);
    }

    /**
     * @return array
     */
    protected function _getSelectedSubCouponIds()
    {
        $subCouponIds = $this->getRequest()->getPost('selected_sub_coupon_ids');
        if ($subCouponIds === null) {
            $subCouponIds = $this->getTieredcoupon()->getSubCouponIds();
            return array_values($subCouponIds);
        }
        return $subCouponIds;
    }
}
