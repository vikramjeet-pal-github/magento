<?php

namespace Vonnda\AlternativeOrigin\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;
use Vonnda\AlternativeOrigin\Api\AlternativeShippingOriginZonesRepositoryInterface;
use Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesInterfaceFactory;
use Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesSearchResultsInterfaceFactory;
use Vonnda\AlternativeOrigin\Model\ResourceModel\AlternativeShippingOriginZones as ResourceAlternativeShippingOriginZones;
use Vonnda\AlternativeOrigin\Model\ResourceModel\AlternativeShippingOriginZones\CollectionFactory as AlternativeShippingOriginZonesCollectionFactory;

class AlternativeShippingOriginZonesRepository implements AlternativeShippingOriginZonesRepositoryInterface
{
    /**
     * @var JoinProcessorInterface
     */
    protected $extensionAttributesJoinProcessor;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var AlternativeShippingOriginZonesSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var ResourceAlternativeShippingOriginZones
     */
    protected $resource;

    /**
     * @var AlternativeShippingOriginZonesFactory
     */
    protected $alternativeShippingOriginZonesFactory;

    /**
     * @var ExtensibleDataObjectConverter
     */
    protected $extensibleDataObjectConverter;

    /**
     * @var AlternativeShippingOriginZonesInterfaceFactory
     */
    protected $dataAlternativeShippingOriginZonesFactory;

    /**
     * @var DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @var AlternativeShippingOriginZonesCollectionFactory
     */
    protected $alternativeShippingOriginZonesCollectionFactory;

    /**
     * AlternativeShippingOriginZonesRepository constructor.
     * @param ResourceAlternativeShippingOriginZones $resource
     * @param AlternativeShippingOriginZonesFactory $alternativeShippingOriginZonesFactory
     * @param AlternativeShippingOriginZonesInterfaceFactory $dataAlternativeShippingOriginZonesFactory
     * @param AlternativeShippingOriginZonesCollectionFactory $alternativeShippingOriginZonesCollectionFactory
     * @param AlternativeShippingOriginZonesSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        ResourceAlternativeShippingOriginZones $resource,
        AlternativeShippingOriginZonesFactory $alternativeShippingOriginZonesFactory,
        AlternativeShippingOriginZonesInterfaceFactory $dataAlternativeShippingOriginZonesFactory,
        AlternativeShippingOriginZonesCollectionFactory $alternativeShippingOriginZonesCollectionFactory,
        AlternativeShippingOriginZonesSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->alternativeShippingOriginZonesFactory = $alternativeShippingOriginZonesFactory;
        $this->alternativeShippingOriginZonesCollectionFactory = $alternativeShippingOriginZonesCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataAlternativeShippingOriginZonesFactory = $dataAlternativeShippingOriginZonesFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesInterface $alternativeShippingOriginZones
    ) {
        $alternativeShippingOriginZonesData = $this->extensibleDataObjectConverter->toNestedArray(
            $alternativeShippingOriginZones,
            [],
            \Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesInterface::class
        );
        $alternativeShippingOriginZonesModel = $this->alternativeShippingOriginZonesFactory->create()->setData(
            $alternativeShippingOriginZonesData
        );

        try {
            $this->resource->save($alternativeShippingOriginZonesModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the zone: %1',
                $exception->getMessage()
            ));
        }
        return $alternativeShippingOriginZonesModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getById($entityId)
    {
        $alternativeShippingOriginZones = $this->alternativeShippingOriginZonesFactory->create();
        $this->resource->load($alternativeShippingOriginZones, $entityId);
        if (!$alternativeShippingOriginZones->getId()) {
            throw new NoSuchEntityException(__('Zone with id "%1" does not exist.', $entityId));
        }
        return $alternativeShippingOriginZones->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->alternativeShippingOriginZonesCollectionFactory->create();
        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesInterface::class
        );
        $this->collectionProcessor->process($criteria, $collection);
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model->getDataModel();
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        \Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesInterface $alternativeShippingOriginZones
    ) {
        try {
            $alternativeShippingOriginZonesModel = $this->alternativeShippingOriginZonesFactory->create();
            $this->resource->load($alternativeShippingOriginZonesModel, $alternativeShippingOriginZones->getEntityId());
            $this->resource->delete($alternativeShippingOriginZonesModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the zone: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($entityId)
    {
        return $this->delete($this->getById($entityId));
    }
}
