<?php
namespace Mexbs\Tieredcoupon\Model\Tieredcoupon;

use Mexbs\Tieredcoupon\Model\ResourceModel\Tieredcoupon\Collection;
use Mexbs\Tieredcoupon\Model\ResourceModel\Tieredcoupon\CollectionFactory;
use Mexbs\Tieredcoupon\Model\Tieredcoupon;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var \Magento\SalesRule\Model\Rule\Metadata\ValueProvider
     */
    protected $metadataValueProvider;

    /**
     * Initialize dependencies.
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();
        /** @var Tieredcoupon $tieredcoupon */
        foreach ($items as $tieredcoupon) {
            $this->loadedData[$tieredcoupon->getId()] = $tieredcoupon->getData();
        }

        return $this->loadedData;
    }
}
