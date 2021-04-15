<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */
namespace Vonnda\TealiumTags\Helper;

use Vonnda\TealiumTags\Model\HttpGateway;
use Vonnda\TealiumTags\Helper\Data as DataObjectHelper;
use Vonnda\Subscription\Helper\Logger;

use Carbon\Carbon;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder as SearchSearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;

class PurchaseSubdata extends AbstractHelper
{
    /**
     * Http Gateway
     *
     * @var \Vonnda\TealiumTags\Model\HttpGateway $customerSession
     */
    protected $httpGateway;

    /**
     * Vonnda Logger
     *
     * @var \Vonnda\Subscription\Helper\Logger $logger
     */
    protected $logger;

    /**
     * Product Repository
     *
     * @var ProductRepository $productRepository
     */
    protected $productRepository;

    /**
     * Search Criteria Builder
     *
     * @var SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * DataObject Helper
     *
     * @var \Vonnda\TealiumTags\Helper\Data $dataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * 
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        HttpGateway $httpGateway,
        Logger $logger,
        DataObjectHelper $dataObjectHelper,
        Context $context,
        ProductRepositoryInterface $productRepository,
        SearchSearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->httpGateway = $httpGateway;
        $this->logger = $logger;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        parent::__construct($context);
    }
    
    public function createPurchaseSubdataEvent($order)
    {
        $utagData = [];
        $utagData['tealium_event'] = 'purchase_subdata_api';
        $utagData['event_action'] = 'Completed Transaction';
        $utagData['event_category'] = 'Ecommerce';
        $utagData['event_value'] = number_format($order->getSubtotal(), 2, '.', '');

        $utagData['customer_email'] = $order->getCustomerEmail();
        $utagData['customer_uid'] = $this->dataObjectHelper->getCustomerUid($order->getCustomerId());

        $utagData = $this->dataObjectHelper->setSubscriptionFieldsByParentOrderId($utagData, $order->getId());

        $utagData = $this->dataObjectHelper->addProductNamesBySubscriptionFields($utagData);

        $success = $this->httpGateway->pushTag($utagData);
        if(!$success){
            $this->logger->info("Failure sending utagData for order " . $order->getId() . ", purchaseSubdataApi event.");
        }
    }

}