<?php

namespace Vonnda\OrderTag\Model;

use Vonnda\OrderTag\Model\ResourceModel\OrderTag\CollectionFactory;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $orderTagCollectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $orderTagCollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $orderTagCollectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        $this->loadedData = [];
        /** @var OrderTag $orderTag */
        foreach ($items as $orderTag) {
            $this->loadedData[$orderTag->getId()]['ordertag'] = $orderTag->getData();
        }

        return $this->loadedData;
    }
}
