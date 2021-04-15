<?php

namespace Vonnda\OrderTag\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\ObjectManagerInterface;
use Vonnda\OrderTag\Model\ResourceModel\OrderTag\CollectionFactory;

class CheckUniqueFrontendDefault implements ObserverInterface
{
    /**
     * @var CollectionFactory
     */
    protected $orderTagCollectionFactory;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    public function __construct(
        CollectionFactory $orderTagCollectionFactory,
        ObjectManagerInterface $objectManager
    ) {
        $this->orderTagCollectionFactory = $orderTagCollectionFactory;
        $this->objectManager = $objectManager;
    }

    public function execute(Observer $observer)
    {
        $orderTagsCollection = $this->orderTagCollectionFactory->create();
        $orderTagsCollection->addFieldToFilter('frontend_default', 1);

        $orderTags = $orderTagsCollection->getData();

        foreach ($orderTags as $data) {
            $model = $this->objectManager->create('Vonnda\OrderTag\Model\OrderTag');
            $model->setData($data);
            $model->setFrontendDefault(0);
            $model->save();
        }
    }
}
