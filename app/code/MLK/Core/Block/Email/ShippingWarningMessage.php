<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace MLK\Core\Block\Email;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\Template\FilterProvider;

class ShippingWarningMessage extends Template
{
    
    const STATIC_BLOCK_ID = "shipping_warning_message";

    const MINIMO_PRODUCT_SKU = "MinimoB_US";

    const MINIMOP_PRODUCT_SKU = "MinimoP_US";
    
    /**
     * Customer Repository Interface
     *
     * @var CustomerRepositoryInterface $customerRepository
     */
    protected $customerRepository;

    /**
     * Search Criteria Builder
     *
     * @var SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Logger
     *
     * @var  LoggerInterface $logger
     */
    protected $logger;

    /**
     * Order Repository
     *
     * @var OrderRepository $orderRepository
     */
    protected $orderRepository;

    /**
     * Product Repository
     *
     * @var ProductRepository $productRepository
     */
    protected $productRepository;

    /**
     * Store Manager
     *
     * @var StoreManager $storeManager
     */
    protected $storeManager;

    /**
     * Block Factory
     *
     * @var BlockFactory $blockFactory
     */
    protected $blockFactory;

    /**
     * Filter Provider
     *
     * @var FilterProvider $filterProvider
     */
    protected $filterProvider;

    /**
     * @param Context $context
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Logger $logger
     * @param OrderRepository $orderRepository
     * @param StoreManagerInterface $storeManager
     * @param BlockFactory $blockFactory
     * @param FilterProvider $filterProvider
     */
    public function __construct(
        Context $context,
        CustomerRepositoryInterface $customerRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger,
        OrderRepository $orderRepository,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        BlockFactory $blockFactory,
        FilterProvider $filterProvider
    ){
        $this->customerRepository = $customerRepository;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->storeManager = $storeManager;
        $this->blockFactory = $blockFactory;
        $this->filterProvider = $filterProvider;
        parent::__construct($context);
	}

    /**
     * Get shipping warning message block
     *
     * @param mixed $order
     * @param array $templateVariables
     * @return string
     */
    public function getShippingWarningMessage($order, $templateVariables = [])
    {
        if(!$order){
            return "";
        }
        
        try {
            $store = $this->storeManager->getStore();
            $block = $this->blockFactory->create()->setStoreId($store->getId())->load(self::STATIC_BLOCK_ID);
            if($block && $block->getIsActive() && $this->orderHasMinimo($order)) {
                $filter = $this->filterProvider
                    ->getBlockFilter()
                    ->setStoreId($store->getId())
                    ->setVariables($templateVariables)
                    ->filter($block->getContent());
                return $filter;
            }
            return "";
        } catch(\Error $e){
            $this->logger->critical($e->getMessage());
        } catch(\Exception $e){
            $this->logger->critical($e->getMessage());
        }
    }

    /**
     * @param mixed $order
     * @return boolean
     */
    public function orderHasMinimo($order)
    {
        $orderItems = $order->getItems();
        foreach($orderItems as $item){
            if($item->getSku() === self::MINIMO_PRODUCT_SKU || $item->getSku() === self::MINIMOP_PRODUCT_SKU){
                return true;
            }
        }

        return false;
    }
}