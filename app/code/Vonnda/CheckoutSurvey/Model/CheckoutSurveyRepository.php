<?php

namespace Vonnda\CheckoutSurvey\Model;

use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterfaceFactory;
use Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveySearchResultsInterfaceFactory;
use Vonnda\CheckoutSurvey\Api\CheckoutSurveyRepositoryInterface;
use Vonnda\CheckoutSurvey\Model\ResourceModel\CheckoutSurvey as ResourceCheckoutSurvey;
use Vonnda\CheckoutSurvey\Model\ResourceModel\CheckoutSurvey\CollectionFactory as CheckoutSurveyCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;

class CheckoutSurveyRepository implements CheckoutSurveyRepositoryInterface
{
    /**
     * @var CheckoutSurveyCollectionFactory
     */
    protected $checkoutSurveyCollectionFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var CheckoutSurveyFactory
     */
    protected $checkoutSurveyFactory;

    /**
     * @var DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @var ResourceCheckoutSurvey
     */
    protected $resource;

    /**
     * @var ExtensibleDataObjectConverter
     */
    protected $extensibleDataObjectConverter;

    /**
     * @var CheckoutSurveySearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var CheckoutSurveyInterfaceFactory
     */
    protected $dataCheckoutSurveyFactory;

    /**
     * @var JoinProcessorInterface
     */
    protected $extensionAttributesJoinProcessor;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;


    /**
     * @param ResourceCheckoutSurvey $resource
     * @param CheckoutSurveyFactory $checkoutSurveyFactory
     * @param CheckoutSurveyInterfaceFactory $dataCheckoutSurveyFactory
     * @param CheckoutSurveyCollectionFactory $checkoutSurveyCollectionFactory
     * @param CheckoutSurveySearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        ResourceCheckoutSurvey $resource,
        CheckoutSurveyFactory $checkoutSurveyFactory,
        CheckoutSurveyInterfaceFactory $dataCheckoutSurveyFactory,
        CheckoutSurveyCollectionFactory $checkoutSurveyCollectionFactory,
        CheckoutSurveySearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->checkoutSurveyFactory = $checkoutSurveyFactory;
        $this->checkoutSurveyCollectionFactory = $checkoutSurveyCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataCheckoutSurveyFactory = $dataCheckoutSurveyFactory;
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
        \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterface $checkoutSurvey
    ) {
        /* if (empty($checkoutSurvey->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $checkoutSurvey->setStoreId($storeId);
        } */
        
        $checkoutSurveyData = $this->extensibleDataObjectConverter->toNestedArray(
            $checkoutSurvey,
            [],
            \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterface::class
        );
        
        $checkoutSurveyModel = $this->checkoutSurveyFactory->create()->setData($checkoutSurveyData);
        
        try {
            $this->resource->save($checkoutSurveyModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the checkoutSurvey: %1',
                $exception->getMessage()
            ));
        }
        return $checkoutSurveyModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getById($checkoutSurveyId)
    {
        $checkoutSurvey = $this->checkoutSurveyFactory->create();
        $this->resource->load($checkoutSurvey, $checkoutSurveyId);
        if (!$checkoutSurvey->getId()) {
            throw new NoSuchEntityException(__('CheckoutSurvey with id "%1" does not exist.', $checkoutSurveyId));
        }
        return $checkoutSurvey->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->checkoutSurveyCollectionFactory->create();
        
        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterface::class
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
        \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterface $checkoutSurvey
    ) {
        try {
            $checkoutSurveyModel = $this->checkoutSurveyFactory->create();
            $this->resource->load($checkoutSurveyModel, $checkoutSurvey->getCheckoutsurveyId());
            $this->resource->delete($checkoutSurveyModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the CheckoutSurvey: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($checkoutSurveyId)
    {
        return $this->delete($this->getById($checkoutSurveyId));
    }
}
