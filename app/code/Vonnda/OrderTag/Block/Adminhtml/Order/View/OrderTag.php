<?php

namespace Vonnda\OrderTag\Block\Adminhtml\Order\View;

use Vonnda\OrderTag\Model\ResourceModel\OrderTag\CollectionFactory as OrderTagCollectionFactory;

class OrderTag extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{
    /**
     * @var OrderTagCollectionFactory
     */
    protected $orderTagCollectionFactory;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        OrderTagCollectionFactory $orderTagCollectionFactory,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $adminHelper,
            $data
        );

        $this->orderTagCollectionFactory = $orderTagCollectionFactory;
    }

    /**
     * Retrieve required options from parent
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    protected function _beforeToHtml()
    {
        if (!$this->getParentBlock()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Please correct the parent block for this block.')
            );
        }
        $this->setOrder($this->getParentBlock()->getOrder());

        parent::_beforeToHtml();
    }

    public function getOrderTag()
    {
        $orderTagId = $this->getOrder()->getOrderTagId();
        $orderTagCollection = $this->orderTagCollectionFactory->create();
        /** @var \Vonnda\OrderTag\Model\OrderTag $orderTag */
        $orderTag = $orderTagCollection->getItemById($orderTagId);

        if (!is_null($orderTag)) {
            return $orderTag->getLabel();
        }

        return null;
    }
}
