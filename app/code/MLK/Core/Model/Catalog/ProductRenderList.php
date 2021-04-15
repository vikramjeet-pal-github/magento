<?php

namespace MLK\Core\Model\Catalog;

use MLK\Core\Api\Catalog\ProductRenderListInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Ui\DataProvider\Product\ProductRenderCollectorComposite;
use Magento\Catalog\Ui\DataProvider\Product\ProductRenderCollectorInterface;
use Magento\Framework\Api\Search\SearchResultFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Data\CollectionModifier;
use Magento\Framework\Data\CollectionModifierInterface;
use Magento\Framework\EntityManager\Operation\Read\ReadExtensions;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;

/**
 * Provide product render information (this information should be enough for rendering product on front)
 * for one or few products
 *
 */
class ProductRenderList implements ProductRenderListInterface
{
    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ProductRenderCollectorInterface
     */
    private $productRenderCollectorComposite;

    /**
     * @var SearchResultFactory
     */
    private $searchResultFactory;

    /**
     * @var ProductRenderFactory
     */
    private $productRenderFactory;

    /**
     * @var array
     */
    private $productAttributes;

    /**
     * @var CollectionModifierInterface
     */
    private $collectionModifier;

    /**
     * @var ReadExtensions
     */
    private $readExtensions;

     /**
     * @var \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface
     */
    protected $extensionAttributesJoinProcessor;

    /**
     * @param CollectionFactory $collectionFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param ProductRenderCollectorComposite $productRenderCollectorComposite
     * @param ProductRenderSearchResultsFactory $searchResultFactory
     * @param ProductRenderFactory $productRenderDtoFactory
     * @param Config $config
     * @param Product\Visibility $productVisibility
     * @param CollectionModifier $collectionModifier
     * @param array $productAttributes
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        ProductRenderCollectorComposite $productRenderCollectorComposite,
        ProductRenderSearchResultsFactory $searchResultFactory,
        ProductRenderFactory $productRenderDtoFactory,
        \Magento\Catalog\Model\Config $config,
        CollectionModifier $collectionModifier,
        array $productAttributes,
        ReadExtensions $readExtensions,
        JoinProcessorInterface $extensionAttributesJoinProcessor
    ) {
        $this->collectionProcessor = $collectionProcessor;
        $this->collectionFactory = $collectionFactory;
        $this->productRenderCollectorComposite = $productRenderCollectorComposite;
        $this->searchResultFactory = $searchResultFactory;
        $this->productRenderFactory = $productRenderDtoFactory;
        $this->productAttributes = array_merge($productAttributes, $config->getProductAttributes());
        $this->collectionModifier = $collectionModifier;
        $this->readExtensions = $readExtensions;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
    }

    /**
     * @inheritdoc
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria, $storeId, $currencyCode)
    {
        $items = [];
        $productCollection = $this->collectionFactory->create();
        $this->extensionAttributesJoinProcessor->process($productCollection);
        $productCollection->addAttributeToSelect('*')
            ->setStoreId($storeId)
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents();

        $this->collectionModifier->apply($productCollection);
        $this->collectionProcessor->process($searchCriteria, $productCollection);
        $productCollection->load();
        foreach ($productCollection->getItems() as $item) {
            $this->readExtensions->execute($item);
        }

        foreach ($productCollection as $item) {
            $productRenderInfo = $this->productRenderFactory->create();
            //Adding sku and bundle product
            $attributes = $item->getExtensionAttributes();
            $bundleOptions = $attributes->getBundleProductOptions();
            $productRenderInfo->setSku($item->getSku());
            $productRenderInfo->setBundleProductOptions($bundleOptions);
            $productRenderInfo->setStoreId($storeId);
			$productRenderInfo->setType("Newtest");
            $productRenderInfo->setCurrencyCode($currencyCode);
            $this->productRenderCollectorComposite->collect($item, $productRenderInfo);
            $items[$item->getId()] = $productRenderInfo;
        }

        $searchResult = $this->searchResultFactory->create();
        $searchResult->setItems($items);
        $searchResult->setTotalCount(count($items));
        $searchResult->setSearchCriteria($searchCriteria);

        return $searchResult;
    }
}
