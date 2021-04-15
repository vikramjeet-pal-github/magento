<?php

namespace Vonnda\DeviceManager\Model;

use Magento\Framework\Reflection\DataObjectProcessor;
use Vonnda\DeviceManager\Api\Data\DeviceManagerInterfaceFactory;
use Vonnda\DeviceManager\Model\ResourceModel\DeviceManager\CollectionFactory as DeviceManagerCollectionFactory;
use Vonnda\DeviceManager\Api\Data\DeviceManagerSearchResultsInterfaceFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Vonnda\DeviceManager\Api\DeviceManagerRepositoryInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Vonnda\DeviceManager\Model\ResourceModel\DeviceManager as ResourceDeviceManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;

class DeviceManagerRepository implements DeviceManagerRepositoryInterface
{
    /**
     * @var DeviceManagerCollectionFactory
     */
    protected $deviceManagerCollectionFactory;

    /**
     * @var DeviceManagerFactory
     */
    protected $deviceManagerFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @var DeviceManagerInterfaceFactory
     */
    protected $dataDeviceManagerFactory;

    /**
     * @var ResourceDeviceManager
     */
    protected $resource;

    /**
     * @var ExtensibleDataObjectConverter
     */
    protected $extensibleDataObjectConverter;

    /**
     * @var DeviceManagerSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var JoinProcessorInterface
     */
    protected $extensionAttributesJoinProcessor;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * DeviceManagerRepository constructor.
     * @param ResourceDeviceManager $resource
     * @param DeviceManagerFactory $deviceManagerFactory
     * @param DeviceManagerInterfaceFactory $dataDeviceManagerFactory
     * @param DeviceManagerCollectionFactory $deviceManagerCollectionFactory
     * @param DeviceManagerSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        ResourceDeviceManager $resource,
        DeviceManagerFactory $deviceManagerFactory,
        DeviceManagerInterfaceFactory $dataDeviceManagerFactory,
        DeviceManagerCollectionFactory $deviceManagerCollectionFactory,
        DeviceManagerSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->deviceManagerFactory = $deviceManagerFactory;
        $this->deviceManagerCollectionFactory = $deviceManagerCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataDeviceManagerFactory = $dataDeviceManagerFactory;
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
        \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface $deviceManager
    ) {
        /* if (empty($deviceManager->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $deviceManager->setStoreId($storeId);
        } */

        $deviceManagerData = $this->extensibleDataObjectConverter->toNestedArray(
            $deviceManager,
            [],
            \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface::class
        );
        
        $deviceManagerModel = $this->deviceManagerFactory->create()->setData($deviceManagerData);
        
        try {
            $this->resource->save($deviceManagerModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the deviceManager: %1',
                $exception->getMessage()
            ));
        }
        return $deviceManagerModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getById($deviceManagerId)
    {
        $deviceManager = $this->deviceManagerFactory->create();
        $this->resource->load($deviceManager, $deviceManagerId);
        if (!$deviceManager->getId()) {
            throw new NoSuchEntityException(__('Device with id "%1" does not exist.', $deviceManagerId));
        }
        return $deviceManager->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->deviceManagerCollectionFactory->create();
        
        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface::class
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
        \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface $deviceManager
    ) {
        try {
            $deviceManagerModel = $this->deviceManagerFactory->create();
            $this->resource->load($deviceManagerModel, $deviceManager->getDevicemanagerId());
            $this->resource->delete($deviceManagerModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Device: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($deviceManagerId)
    {
        return $this->delete($this->getById($deviceManagerId));
    }
}
