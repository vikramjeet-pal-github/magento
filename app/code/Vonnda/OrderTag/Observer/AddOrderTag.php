<?php

namespace Vonnda\OrderTag\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Vonnda\OrderTag\Model\ResourceModel\OrderTag\CollectionFactory as OrderTagCollectionFactory;

class AddOrderTag implements ObserverInterface
{
    /**
     * @var OrderTagCollectionFactory
     */
    protected $orderTagCollectionFactory;

    public function __construct(
        OrderTagCollectionFactory $orderTagCollectionFactory
    ) {
        $this->orderTagCollectionFactory = $orderTagCollectionFactory;
    }

    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        $orderTagCollection = $this->orderTagCollectionFactory->create();

        $orderTag = $orderTagCollection->addFieldToFilter('frontend_default', ['eq' => 1]);

        if ($orderTag->count()) {
            $order->setOrderTagId((int) $orderTag->getFirstItem()->getId());
        }

        return $this;
    }
}
