<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Block;

use Vonnda\Subscription\Model\SubscriptionCustomerRepository;
use Vonnda\Subscription\Model\SubscriptionOrderRepository;
use Vonnda\Subscription\Model\SubscriptionProductRepository;
use Vonnda\Subscription\Model\SubscriptionPlanRepository;
use Vonnda\Subscription\Model\SubscriptionPaymentRepository;
use Vonnda\Subscription\Model\SubscriptionPaymentFactory;
use Vonnda\Subscription\Model\SubscriptionService;
use Vonnda\DeviceManager\Api\DeviceManagerRepositoryInterface;
use Vonnda\Subscription\Helper\AddressHelper;
use Vonnda\Subscription\Helper\Logger;
use Vonnda\Subscription\Helper\Data as Helper;
use Vonnda\Subscription\Api\Data\SubscriptionCustomerEstimateQueryInterfaceFactory;

use Carbon\Carbon;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;

class EmailBlock extends Template
{
    
    protected $_order;

    protected $_product;
    
    /**
     * Subscription Customer Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionCustomerRepository $subscriptionCustomerRepository
     */
    protected $subscriptionCustomerRepository;

    /**
     * Subscription Order Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionOrderRepository $subscriptionOrderRepository
     */
    protected $subscriptionOrderRepository;

    /**
     * Subscription Product Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionProductRepository $subscriptionProductRepository
     */
    protected $subscriptionProductRepository;

    /**
     * Subscription Plan Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionPlanRepository $subscriptionPlanRepository
     */
    protected $subscriptionPlanRepository;

    /**
     * Subscription Payment Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionPaymentRepository $subscriptionPaymentRepository
     */
    protected $subscriptionPaymentRepository;

    /**
     * Device Manager Repository
     *
     * @var \Vonnda\DeviceManager\Api\DeviceManagerRepositoryInterface $subscriptionDeviceRepository
     */
    protected $subscriptionDeviceRepository;

    /**
     * Helper
     *
     * @var \Vonnda\Subscription\Helper\Data $helper
     */
    protected $helper;

    /**
     * Address Repository
     *
     * @var \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     */
    protected $addressRepository;

    /**
     * Customer Repository Interface
     *
     * @var \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    protected $customerRepository;

    /**
     * Search Criteria Builder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Vonnda Logger
     *
     * @var \Vonnda\Subscription\Helper\Logger $logger
     */
    protected $logger;

    /**
     * Order Repository
     *
     * @var \Magento\Sales\Model\OrderRepository $orderRepository
     */
    protected $orderRepository;

    /**
     * Product Repository
     *
     * @var \Magento\Catalog\Model\ProductRepository $productRepository
     */
    protected $productRepository;

    protected $subscriptionService;
    
    /**
     *
     * @var SubscriptionCustomerEstimateQueryInterfaceFactory $subscriptionCustomerEstimateQueryFactory
     */
    protected $subscriptionCustomerEstimateQueryFactory;

    protected $storeManager;

    /**
     *
     * @var BlockFactory $blockFactory
     */
    protected $blockFactory;

    /**
     *
     * @var FilterProvider $filterProvider
     */
    protected $filterProvider;

    /**
     * 
     * Subscription Customer Info Block Constructor
     * 
     * @param Context $context
     * @param RequestInterface $request
     * @param AddressRepositoryInterface $addressRepository
     * @param SubscriptionCustomerRepository $subscriptionCustomerRepository
     * @param SubscriptionOrderRepository $subscriptionOrderRepository
     * @param SubscriptionPlanRepository $subscriptionPlanRepository
     * @param SubscriptionPaymentRepository $subscriptionPaymentRepository
     * @param SubscriptionPaymentFactory $subscriptionPaymentFactory
     * @param AddressHelper $addressHelper
     * @param VonndaStripeHelper $vonndaStripeHelper
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderPaymentRepository $orderPaymentRepository
     * @param OrderRepository $orderRepository
     * @param Logger $logger
     * @param StripeCustomerfactory $stripeCustomerFactory
     * @param CheckoutAgreementsListInterface $checkoutAgreementsList
     * @param SubscriptionCustomerEstimateQueryInterfaceFactory $subscriptionCustomerEstimateQueryFactory
     * @param BlockFactory $blockFactory
     * @param FilterProvider $filterProvider
     */
    public function __construct(
        Context $context,
        AddressRepositoryInterface $addressRepository,
        SubscriptionCustomerRepository $subscriptionCustomerRepository,
        SubscriptionOrderRepository $subscriptionOrderRepository,
        SubscriptionPlanRepository $subscriptionPlanRepository,
        SubscriptionProductRepository $subscriptionProductRepository,
        SubscriptionPaymentRepository $subscriptionPaymentRepository,
        DeviceManagerRepositoryInterface $subscriptionDeviceRepository,
        SubscriptionPaymentFactory $subscriptionPaymentFactory,
        CustomerRepositoryInterface $customerRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Logger $logger,
        ProductRepositoryInterface $productRepository,
        SubscriptionService $subscriptionService,
        SubscriptionCustomerEstimateQueryInterfaceFactory $subscriptionCustomerEstimateQueryFactory,
        Helper $helper,
        StoreManagerInterface $storeManager,
        OrderRepositoryInterface $orderRepository
    ){
        $this->addressRepository = $addressRepository;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->subscriptionProductRepository = $subscriptionProductRepository;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
        $this->subscriptionPaymentFactory = $subscriptionPaymentFactory;
        $this->subscriptionOrderRepository = $subscriptionOrderRepository;
        $this->subscriptionPlanRepository = $subscriptionPlanRepository;
        $this->subscriptionDeviceRepository = $subscriptionDeviceRepository;
        $this->customerRepository = $customerRepository;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->subscriptionService = $subscriptionService;
        $this->logger = $logger;
        $this->subscriptionCustomerEstimateQueryFactory = $subscriptionCustomerEstimateQueryFactory;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->orderRepository = $orderRepository;
        parent::__construct($context);
	}

    public function getSubscriptionPlanSubtotal($subscriptionCustomer)
    {
        if($subscriptionCustomer){
            $estamateRequest = $this->subscriptionCustomerEstimateQueryFactory->create();
            $estamateRequest->setSubscriptionId($subscriptionCustomer->getId());
            $estamateRequest->setShippingAddressId($subscriptionCustomer->getShippingAddressId());
            $estimate = $this->subscriptionService->getSubscriptionCustomerEstimate($estamateRequest);
            return '$'.number_format($estimate->getSubtotal(), 2, '.', ',');
        }
        return "";
    }

    public function getSubscriptionPlanTotal($subscriptionCustomer)
    {
        if($subscriptionCustomer){
            $estimateRequest = $this->subscriptionCustomerEstimateQueryFactory->create();
            $estimateRequest->setSubscriptionId($subscriptionCustomer->getId());
            $estimateRequest->setShippingAddressId($subscriptionCustomer->getShippingAddressId());
            $estimate = $this->subscriptionService->getSubscriptionCustomerEstimate($estimateRequest);
            return '$'.number_format($estimate->getOrderTotal(), 2, '.', ',');
        }
        return "";
    }

	public function getSubscriptionProductsData($subscriptionPlan)
	{
        $returnArr = [];
        if($subscriptionPlan){
            $subscriptionProducts = $this->subscriptionProductRepository
                ->getSubscriptionProductsByPlanId($subscriptionPlan->getId());
            foreach($subscriptionProducts->getItems() as $item){
                $product = $this->productRepository->getById($item->getProductId());
                $returnArr[] = array_merge($item->getData(), ["name" => $product->getDescription()]);
            }
        }
		return $returnArr;
    }

    public function getAutorefillUrl($subscriptionCustomer)
    {
        $store = $this->storeManager->getStore();
        $baseUrl = $store->getBaseUrl();
        if($subscriptionCustomer){
            return $baseUrl . "subscription/customer/autorefill?subscription=" . $subscriptionCustomer->getId();
        }
        return $baseUrl . "subscription/customer/autorefill";
    }

    public function getCMSBlockHtml($blockIdentifier, $templateVariables = [])
    {
        return $this->helper->getCMSBlockHtml($blockIdentifier, $templateVariables);
    }

    public function getSubscriptionParentOrder($subscriptionCustomer)
    {
        $parentOrderId = $subscriptionCustomer->getParentOrderId();
        if(!$parentOrderId){
            return null;
        }

        try {
            return $this->orderRepository->get($parentOrderId);
        } catch(\Exception $e){
            return null;
        }
    }

    public function setOrder($order)
    {
        $this->_order = $order;
        return $this;
    }

    public function setProduct($product)
    {
        $this->_product = $product;
        return $this;
    }

    public function getReturnTotals()
    {
        //This would have to pull from the corresponding credit memo, this e-mail is currently disabled until further notice
        $totals = [];
        if($this->_order && $this->_product){
            $totals["Subtotal"] = "$" . (string)number_format((float)$this->_product->getPrice(), 2, '.', '');
            $totals["Return Total"] = "$" . (string)number_format((float)$this->_product->getPrice(), 2, '.', '');
            return $totals;
        }

        return $totals;
    }

}