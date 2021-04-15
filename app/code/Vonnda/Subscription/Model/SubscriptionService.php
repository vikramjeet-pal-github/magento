<?php 
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Model;

use Magento\Framework\App\Area;
use Vonnda\Subscription\Model\SubscriptionCustomer;
use Vonnda\Subscription\Model\SubscriptionCustomerFactory;
use Vonnda\Subscription\Model\SubscriptionCustomerEstimateFactory;
use Vonnda\Subscription\Model\SubscriptionPaymentFactory;
use Vonnda\Subscription\Model\SubscriptionPromoFactory;
use Vonnda\DeviceManager\Api\DeviceManagerRepositoryInterface;
use Vonnda\DeviceManager\Model\DeviceManagerFactory;
use Vonnda\Subscription\Helper\AddressHelper;
use Vonnda\Subscription\Helper\TimeDateHelper;
use Vonnda\Subscription\Helper\PromoHelper;
use Vonnda\Subscription\Helper\Logger as SubscriptionLogger;
use Vonnda\Subscription\Helper\Data as SubscriptionHelper;
use Vonnda\Subscription\Helper\DeviceHelper;
use Vonnda\Subscription\Helper\ValidationHelper;
use Vonnda\Subscription\Helper\EmailHelper;
use Vonnda\Subscription\Api\SubscriptionServiceInterface;
use Vonnda\Subscription\Model\Customer\AddressFactory;
use Vonnda\TealiumTags\Helper\SubscriptionService as TealiumHelper;
use Vonnda\Netsuite\Model\Client as NetsuiteClient;
use Carbon\Carbon;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\State;
use Vonnda\TealiumTags\Helper\DeviceRegistration;

class SubscriptionService implements SubscriptionServiceInterface
{
    /**
     * Subscription Customer Model Factory
     *
     * @var \Vonnda\Subscription\Model\SubscriptionCustomer $subscriptionCustomerFactory
     */
    protected $subscriptionCustomerFactory;

    /**
     * Subscription Payment Model Factory
     *
     * @var \Vonnda\Subscription\Model\SubscriptionPayment $subscriptionPaymentFactory
     */
    protected $subscriptionPaymentFactory;

    /**
     * Subscription Promo Model Factory
     *
     * @var \Vonnda\Subscription\Model\SubscriptionPromo $subscriptionPromoFactory
     */
    protected $subscriptionPromoFactory;
    
    /**
     * Subscription Customer Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionCustomerRepository $subscriptionCustomerRepository
     */
    protected $subscriptionCustomerRepository;

    /**
     * Subscription Payment Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionPaymentRepository $subscriptionPaymentRepository
     */
    protected $subscriptionPaymentRepository;

    /**
     * Subscription Promo Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionPromoRepository $subscriptionPromoRepository
     */
    protected $subscriptionPromoRepository;

    /**
     * Subscription Plan Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionPlanRepository $subscriptionPlanRepository
     */
    protected $subscriptionPlanRepository;

    /**
     * Subscription Device Factory
     *
     * @var \Vonnda\DeviceManager\Model\DeviceManagerFactory $subscriptionDeviceFactory
     */
    protected $subscriptionDeviceFactory;

    /**
     * Device Manager Repository
     *
     * @var \Vonnda\DeviceManager\Api\DeviceManagerRepositoryInterface $subscriptionDeviceRepository
     */
    protected $subscriptionDeviceRepository;

    /**
     * Address Helper
     *
     * @var \Vonnda\Subscription\Helper\AddressHelper $addressHelper
     */
    protected $addressHelper;

    /**
     * Device Helper
     *
     * @var \Vonnda\Subscription\Helper\DeviceHelper $deviceHelper
     */
    protected $deviceHelper;

    /**
     * Validation Helper
     *
     * @var \Vonnda\Subscription\Helper\ValidationHelper $validationHelper
     */
    protected $validationHelper;

    /**
     * Email Helper
     *
     * @var \Vonnda\Subscription\Helper\EmailHelper $emailHelper
     */
    protected $emailHelper;

    /**
     * Time Date Helper
     *
     * @var \Vonnda\Subscription\Helper\TimeDateHelper $timeDateHelper
     */
    protected $timeDateHelper;

    /**
     * Promo Helper
     *
     * @var \Vonnda\Subscription\Helper\PromoHelper $promoHelper
     */
    protected $promoHelper;

    /**
     * Subscription Helper
     *
     * @var \Vonnda\Subscription\Helper\Data $subscriptionHelper
     */
    protected $subscriptionHelper;

    /**
     * Tealium Helper
     *
     * @var \Vonnda\TealiumTags\Helper\SubscriptionService $tealiumHelper
     */
    protected $tealiumHelper;

    /**
     * Subscription Logger Helper
     *
     * @var \Vonnda\Subscription\Helper\Logger $subscriptionLogger
     */
    protected $subscriptionLogger;

    /**
     * Search Criteria Builder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Address Repository
     *
     * @var \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     */
    protected $addressRepository;

    /**
     * User Context Interface
     *
     * @var \Magento\Authorization\Model\UserContextInterface $userContext
     */
    protected $userContext;

    /**
     * Request
     *
     * @var \Magento\Framework\App\RequestInterface $request
     */
    protected $request;

    /**
     * Customer Repository
     *
     * @var \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    protected $customerRepository;

    /**
     * Quote Factory
     *
     * @var \Magento\Quote\Model\QuoteFactory $quoteFactory
     */
    protected $quoteFactory;

    /**
     * Subscription Product Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionProductRepository $subscriptionProductRespository
     */
    protected $subscriptionProductRepository;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var SubscriptionCustomerEstimateFactory
     */
    protected $subscriptionCustomerEstimateFactory;

    /**
     * Address Factory
     *
     * @var \Vonnda\Subscription\Model\Customer\AddressFactory $addressFactory
     */
    protected $addressFactory;

    /**
     * Store Manager Interface
     *
     * @var \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    protected $storeManager;
    
     /**
     * Quote Totals Collector
     *
     * @var TotalsCollector $totalsCollector
     */
    protected $totalsCollector;

    /**
     * Netsuite Client
     *
     * @var NetsuiteClient $netsuiteClient
     */
    protected $netsuiteClient;

    protected $scopeConfig;
    protected $appState;
    protected $deviceRegistration;

    /**
     * Subscription Service - For use in WebApi
     * @param SubscriptionCustomerFactory $subscriptionCustomerFactory
     * @param SubscriptionPaymentFactory $subscriptionPaymentFactory
     * @param SubscriptionPromoFactory $subscriptionPromoFactory
     * @param AddressHelper $addressHelper
     * @param SubscriptionPlanRepository $subscriptionPlanRepository
     * @param SubscriptionPaymentRepository $subscriptionPaymentRepository
     * @param SubscriptionPromoRepository $subscriptionPromoRepository
     * @param SubscriptionCustomerRepository $subscriptionCustomerRepository
     * @param DeviceManagerFactory $subscriptionDeviceFactory
     * @param DeviceManagerRepositoryInterface $subscriptionDeviceRepository
     * @param AddressHelper $addressHelper
     * @param TimeDateHelper $timeDateHelper
     * @param PromoHelper $promoHelper
     * @param DeviceHelper $deviceHelper
     * @param ValidationHelper $validationHelper
     * @param EmailHelper $emailHelper
     * @param SubscriptionHelper $subscriptionHelper
     * @param TealiumHelper $tealiumHelper
     * @param SubscriptionLogger $subscriptionLogger
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param AddressRepositoryInterface $addressRepository
     * @param UserContextInterface $userContext
     * @param RequestInterface $request
     * @param CustomerRepositoryInterface $customerRepository
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param SubscriptionProductRepository $subscriptionProductRepository
     * @param ProductRepository $productRepository
     * @param SubscriptionCustomerEstimateFactory $subscriptionCustomerEstimateFactory
     * @param AddressFactory $addressFactory
     * @param StoreManagerInterface $storeManager
     * @param TotalsCollector $totalsCollector
     * @param NetsuiteClient $netsuiteClient
     * @param ScopeConfigInterface $scopeConfig
     * @param State $appState
     * @param DeviceRegistration $deviceRegistration
     */
    public function __construct(
        SubscriptionCustomerFactory $subscriptionCustomerFactory,
        SubscriptionPaymentFactory $subscriptionPaymentFactory,
        SubscriptionPromoFactory $subscriptionPromoFactory,
        SubscriptionPlanRepository $subscriptionPlanRepository,
        SubscriptionPaymentRepository $subscriptionPaymentRepository,
        SubscriptionPromoRepository $subscriptionPromoRepository,
        SubscriptionCustomerRepository $subscriptionCustomerRepository,
        DeviceManagerFactory $subscriptionDeviceFactory,
        DeviceManagerRepositoryInterface $subscriptionDeviceRepository,
        AddressHelper $addressHelper,
        TimeDateHelper $timeDateHelper,
        PromoHelper $promoHelper,
        DeviceHelper $deviceHelper,
        ValidationHelper $validationHelper,
        EmailHelper $emailHelper,
        SubscriptionHelper $subscriptionHelper,
        TealiumHelper $tealiumHelper,
        SubscriptionLogger $subscriptionLogger,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AddressRepositoryInterface $addressRepository,
        UserContextInterface $userContext,
        RequestInterface $request,
        CustomerRepositoryInterface $customerRepository,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        SubscriptionProductRepository $subscriptionProductRepository,
        ProductRepository $productRepository,
        SubscriptionCustomerEstimateFactory $subscriptionCustomerEstimateFactory,
        AddressFactory $addressFactory,
        StoreManagerInterface $storeManager,
        TotalsCollector $totalsCollector,
        NetsuiteClient $netsuiteClient,
        ScopeConfigInterface $scopeConfig,
        State $appState,
        DeviceRegistration $deviceRegistration
    ) {
        $this->subscriptionCustomerFactory = $subscriptionCustomerFactory;
        $this->subscriptionPaymentFactory = $subscriptionPaymentFactory;
        $this->subscriptionPromoFactory = $subscriptionPromoFactory;
        $this->subscriptionPlanRepository = $subscriptionPlanRepository;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
        $this->subscriptionPromoRepository = $subscriptionPromoRepository;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->subscriptionDeviceFactory = $subscriptionDeviceFactory;
        $this->subscriptionDeviceRepository = $subscriptionDeviceRepository;
        $this->addressHelper = $addressHelper;
        $this->timeDateHelper = $timeDateHelper;
        $this->promoHelper = $promoHelper;
        $this->deviceHelper = $deviceHelper;
        $this->validationHelper = $validationHelper;
        $this->emailHelper = $emailHelper;
        $this->subscriptionHelper = $subscriptionHelper;
        $this->tealiumHelper = $tealiumHelper;
        $this->subscriptionLogger = $subscriptionLogger;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->addressRepository = $addressRepository;
        $this->userContext = $userContext;
        $this->request = $request;
        $this->customerRepository = $customerRepository;
        $this->quoteFactory = $quoteFactory;
        $this->subscriptionProductRepository = $subscriptionProductRepository;
        $this->productRepository = $productRepository;
        $this->subscriptionCustomerEstimateFactory = $subscriptionCustomerEstimateFactory;
        $this->addressFactory = $addressFactory;
        $this->storeManager = $storeManager;
        $this->totalsCollector = $totalsCollector;
        $this->netsuiteClient = $netsuiteClient;
        $this->scopeConfig = $scopeConfig;
        $this->appState = $appState;
        $this->deviceRegistration = $deviceRegistration;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscriptionPlans()
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('status','active','eq')
            ->create();
        $subscriptionPlans = [];
        $subscriptionPlanList = $this->subscriptionPlanRepository->getList($searchCriteria);
        foreach($subscriptionPlanList->getItems() as $item){
            $subscriptionPlans[] = $item;
        }
        return $subscriptionPlans;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscriptionCustomer()
    {
        $customerId = $this->userContext->getUserId();
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(
                'customer_id',
                $customerId,
                'eq'
            )->create();

        $subscriptionCustomers = [];
        $subscriptionCustomerList = $this->subscriptionCustomerRepository->getList($searchCriteria);
        foreach ($subscriptionCustomerList->getItems() as $item) {
            $subscriptionCustomers[] = $item;
        }
        return $subscriptionCustomers;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscriptionCustomerBySerialNumber(int $customerId, string $serialNumber)
    {
        if (empty($customerId) || empty($serialNumber)) return false;
        $searchCriteria = $this->searchCriteriaBuilder
        ->addFilter(
            'serial_number',
            $serialNumber,
            'eq'
        )
        ->addFilter(
            'customer_id',
            $customerId,
            'eq'
        )->create();
        
        $subscriptionDeviceList = $this->subscriptionDeviceRepository->getList($searchCriteria);
        foreach ($subscriptionDeviceList->getItems() as $device) {
            $customerSearchCriteria = $this->searchCriteriaBuilder->addFilter(
                'customer_id',
                $customerId,
                'eq'
            )->addFilter(
                'device_id',
                $device->getEntityId(),
                'eq'
            )->create();
            $subscriptionCustomers = [];
            $subscriptionCustomerList = $this->subscriptionCustomerRepository->getList($customerSearchCriteria);
            foreach ($subscriptionCustomerList->getItems() as $subscriptionCustomer) {
                return $subscriptionCustomer;
            }
        }
        return false;

    }


    /**
     * {@inheritdoc}
     */
    public function getSubscriptionCustomerById(int $customerId)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            'customer_id',
            $customerId,
            'eq'
        )->create();

        $subscriptionCustomers = [];
        $subscriptionCustomerList = $this->subscriptionCustomerRepository->getList($searchCriteria);
        foreach ($subscriptionCustomerList->getItems() as $item) {
            $subscriptionCustomers[] = $item;
        }
        return $subscriptionCustomers;
    }

    /**
     * {@inheritdoc}
     */
    public function listSubscriptionCustomer(SearchCriteriaInterface $searchCriteria)
    {
        $subscriptionCustomers = [];
        $subscriptionCustomerList = $this->subscriptionCustomerRepository->getList($searchCriteria);
        foreach($subscriptionCustomerList->getItems() as $item){
            $subscriptionCustomers[] = $item;
        }

        return $subscriptionCustomers;
    }

    /**
     * {@inheritdoc}
     */
    public function createSubscriptionCustomer(\Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer)
    {
        try {
            $customerId = $this->userContext->getUserId();
            $customer = $this->customerRepository->getById($customerId);
            $subscriptionPlan = $subscriptionCustomer->getSubscriptionPlan();
            $subscriptionDevice = $subscriptionCustomer->getDevice();
            if ($subscriptionDevice && $subscriptionDevice->getSerialNumber()) {
                if ($this->isExistingDevice($subscriptionDevice->getSerialNumber()) || (
                        ($salesChannel = $this->netsuiteClient->verifySerialOnNetsuite($subscriptionDevice->getSerialNumber())) == null &&
                        $this->scopeConfig->getValue('vonnda_subscriptions_general/serial_number/api_require_valid', ScopeInterface::SCOPE_STORE, $this->storeManager->getStore()->getId()) == 1
                    )) {
                    //the device and payment are persisted already by the api
                    if ($subscriptionDevice) {
                        $this->subscriptionDeviceRepository->delete($subscriptionDevice);
                    }
                    if ($subscriptionCustomer->getPayment()) {
                        $this->subscriptionPaymentRepository->delete($subscriptionCustomer->getPayment());
                    }
                    throw new \Exception("Invalid serial number.");
                }
                $subscriptionDevice->setIsSerialNumberValid($salesChannel === null ? 0 : 1);
                $subscriptionDevice->setSalesChannel($salesChannel);
                $this->subscriptionDeviceRepository->save($subscriptionDevice);
            }
            if ($subscriptionDevice->getPurchaseDate()) {
                $startDate = Carbon::createFromFormat('Y-m-d H:i:s', $subscriptionDevice->getPurchaseDate());
            } else {
                $startDate = Carbon::createMidnightDate();
            }
            $purchaseDate = $startDate->format('m/d/Y'); // setting this here because $startDate will change after the line below
            $nextOrderDate = $this->timeDateHelper->getNextDateFromFrequencyWithStart($subscriptionPlan->getFrequency(), $subscriptionPlan->getFrequencyUnits(), $startDate);
            $subscriptionCustomer->setCustomerId($customerId)->setNextOrder($nextOrderDate);
            $this->subscriptionCustomerRepository->save($subscriptionCustomer);
            $this->netsuiteClient->sendCustomerInfoToNetsuite($customer, $subscriptionCustomer);
            if ($subscriptionCustomer->getStatus() === SubscriptionCustomer::AUTORENEW_ON_STATUS) {
                $this->emailHelper->sendSignupSuccessEmail($customer, ["subscriptionCustomer" => $subscriptionCustomer]);
            }
            if ($this->appState->getAreaCode() === Area::AREA_WEBAPI_REST) {
                $this->deviceRegistration->createRegisterDeviceSubmitEvent($customer, $subscriptionCustomer, $purchaseDate, $subscriptionDevice->getSalesChannel(), 'App');
            }
            $searchCriteria = $this->searchCriteriaBuilder->addFilter('customer_id', $customerId, 'eq')->create();
            $subscriptionCustomers = [];
            $subscriptionCustomerList = $this->subscriptionCustomerRepository->getList($searchCriteria);
            foreach ($subscriptionCustomerList->getItems() as $item) {
                $subscriptionCustomers[] = $item;
            }
            return $subscriptionCustomers;
        } catch (\Exception $e) {
            $this->deviceRegistration->createRegisterDeviceFailEvent($subscriptionDevice->getSerialNumber(), 'App');
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateSubscriptionCustomer(\Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer)
    {
        $customerId = $this->userContext->getUserId();
        $customer = $this->customerRepository->getById($customerId);

        $subscriptionBeforeUpdate = $this->subscriptionCustomerRepository->getById($subscriptionCustomer->getId());
        $oldStatus = $subscriptionBeforeUpdate->getStatus();

        if($subscriptionCustomer->getState() !== SubscriptionCustomer::ERROR_STATE){
            $subscriptionCustomer->setErrorMessage(NULL);
        }

        $this->subscriptionCustomerRepository->save($subscriptionCustomer);
        //reload the subscription in case some fields weren't passed through the API
        $subscriptionCustomer = $this->subscriptionCustomerRepository->getById($subscriptionCustomer->getId());

        // If update is to activate subscription and next order date is in the past, then set to today.
        if($subscriptionCustomer->getStatus() === SubscriptionCustomer::AUTORENEW_ON_STATUS){
            if(Carbon::parse($subscriptionCustomer->getNextOrder())->lt(Carbon::now())){
                $subscriptionCustomer->setNextOrder(Carbon::now()->toDateTimeString());
                $this->subscriptionCustomerRepository->save($subscriptionCustomer);
            }
        }        
        
        $statusHasChanged = $oldStatus !== $subscriptionCustomer->getStatus();

        $now = Carbon::now()->toDateTimeString();
        $emailTemplateVariables = [
            "subscriptionCustomer" => $subscriptionCustomer,
            "now" => $now
        ];

        $oldStatusWasError = $oldStatus === SubscriptionCustomer::PAYMENT_EXPIRED_STATUS
            || $oldStatus === SubscriptionCustomer::PAYMENT_INVALID_STATUS
            || $oldStatus === SubscriptionCustomer::PROCESSING_ERROR_STATUS;
            
        if(!$oldStatusWasError && $statusHasChanged && $subscriptionCustomer->getStatus() === SubscriptionCustomer::AUTORENEW_ON_STATUS){
            $this->emailHelper->sendSignupSuccessEmail($customer, $emailTemplateVariables);
            $this->tealiumHelper->createActivateAutoRenewEvent($customer, $subscriptionCustomer);
        }
        if($statusHasChanged && $subscriptionCustomer->getStatus() === SubscriptionCustomer::AUTORENEW_OFF_STATUS){
            $this->emailHelper->sendTurnOffAutoRenewEmail($customer, $emailTemplateVariables);
            $this->tealiumHelper->createTurnOffAutoRenewEvent($customer, $subscriptionCustomer);
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('customer_id', $customerId,'eq')
            ->create();
    
        $subscriptionCustomers = [];
        $subscriptionCustomerList = $this->subscriptionCustomerRepository->getList($searchCriteria);
        foreach($subscriptionCustomerList->getItems() as $item){
            $subscriptionCustomers[] = $item;
        }
        return $subscriptionCustomers;
    }

    /**
     * {@inheritdoc}
     */
    public function updateSubscriptionCustomerRenewal(\Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer)
    {
        $customerId = $this->userContext->getUserId();
        $customer = $this->customerRepository->getById($customerId);

        $subscriptionBeforeUpdate = $this->subscriptionCustomerRepository->getById($subscriptionCustomer->getId());
        $oldStatus = $subscriptionBeforeUpdate->getStatus();

        if($subscriptionCustomer->getState() !== SubscriptionCustomer::ERROR_STATE){
            $subscriptionCustomer->setErrorMessage(NULL);
        }
		$nextOrder = Carbon::createFromFormat("m/d/Y", $subscriptionCustomer->getNextOrder());
		$nextOrder = $nextOrder->addDays(1);
		$nextOrder->startOfDay()->toDateTimeString();
        $subscriptionCustomer->setNextOrder($nextOrder);
        $this->subscriptionCustomerRepository->save($subscriptionCustomer);
        //reload the subscription in case some fields weren't passed through the API
        $subscriptionCustomer = $this->subscriptionCustomerRepository->getById($subscriptionCustomer->getId());

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('customer_id', $customerId,'eq')
            ->create();
    
        $subscriptionCustomers = [];
        $subscriptionCustomerList = $this->subscriptionCustomerRepository->getList($searchCriteria);
        foreach($subscriptionCustomerList->getItems() as $item){
            $subscriptionCustomers[] = $item;
        }
        return $subscriptionCustomers;
    }


    /**
     * {@inheritdoc}
     */
    public function getSubscriptionCustomerEstimate(\Vonnda\Subscription\Api\Data\SubscriptionCustomerEstimateQueryInterface $subscriptionCustomerEstimateQuery)
    {
        $freeShipping = false;
        /** @var SubscriptionCustomer $subscriptionCustomer */
        $_subscriptionCustomer = $this->subscriptionCustomerRepository->getById($subscriptionCustomerEstimateQuery->getSubscriptionId());
        $subscriptionPlan = $_subscriptionCustomer->getSubscriptionPlan();
        $customer = $this->customerRepository->getById($_subscriptionCustomer->getCustomerId());
        
        if ($subscriptionPlan && $subscriptionPlan->getStoreId()) {
            $store = $this->storeManager->getStore($subscriptionPlan->getStoreId());
        } else {
            $store = $this->storeManager->getStore($customer->getStoreId());
        }
        
        $quote = $this->quoteFactory->create();
        $quote->setStore($store);
        $quote->setStoreId($store->getId());
        $quote->setCurrency();

        $quote->assignCustomer($customer);

        $address = $this->addressFactory->create()->load($subscriptionCustomerEstimateQuery->getShippingAddressId());
        $quote->getShippingAddress()->addData($address->getData());
        $quote->getBillingAddress()->addData($address->getData());


        $subscriptionPlanId = $subscriptionPlan->getId();
        $subscriptionProducts = $this->subscriptionProductRepository->getSubscriptionProductsByPlanId($subscriptionPlanId);
        $itemsCount = 0;
        $itemsQty = 0;
        foreach ($subscriptionProducts->getItems() as $subscriptionProduct) {
            $product = $this->productRepository->getById($subscriptionProduct->getProductId());
            $priceOverride = $subscriptionProduct->getPriceOverride();
            if ($priceOverride) {
                $product->setPrice($priceOverride)->setBasePrice($priceOverride);
            }

            $quote->addProduct($product, $subscriptionProduct->getQty());
            $itemsQty = $itemsQty + $subscriptionProduct->getQty();
            $itemsCount++;
        }
        $quote->setItemsQty($itemsQty);
        $quote->setItemsCount($itemsCount);

        $this->setShippingMethodOnQuote($quote, $_subscriptionCustomer, $freeShipping);

        $quote = $this->setCouponOnQuote(
            $subscriptionCustomerEstimateQuery,
            $_subscriptionCustomer,
            $quote);

        $quote->collectTotals();
        $quote->setIsActive(0);
        /** @var SubscriptionCustomerEstimate $subscriptionCustomerEstimate */
        $subscriptionCustomerEstimate = $this->subscriptionCustomerEstimateFactory->create();
        $subscriptionCustomerEstimate->setOrderTotal($quote->getGrandTotal())
            ->setSubtotal($quote->getSubtotal())
            ->setPromoCode($quote->getCouponCode())
            ->setShipping($quote->getShippingAddress()->getShippingAmount())
            ->setTax($quote->getShippingAddress()->getTaxAmount());

        return $subscriptionCustomerEstimate;
    }

    /**
     * Configure shipping option for quote
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @param $freeShipping
     * @return void
     * 
     */
    protected function setShippingMethodOnQuote(
        $quote, 
        \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer,
        $freeShipping = false
    ){
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true);
        $this->totalsCollector->collectAddressTotals($quote, $shippingAddress);
        
        if($subscriptionCustomer->getShippingMethodOverwrite()){
            $shippingAddress->setShippingMethod($subscriptionCustomer->getShippingMethodOverwrite());
            $shippingPriceOverride = false;  //Config value intended only for cron config shipping method
            $shouldApplyCostOverwrite = $subscriptionCustomer->getShippingCostOverwrite() 
                || $this->subscriptionHelper->isZeroNumber($subscriptionCustomer->getShippingCostOverwrite());
            if($shouldApplyCostOverwrite){
                $shippingPriceOverride = $subscriptionCustomer->getShippingCostOverwrite();
                $shippingAddress->setCollectShippingRates(true)
                    ->collectShippingRatesWithOverride($shippingPriceOverride, $this->storeManager);
                return;
            }

            $shippingAddress->setCollectShippingRates(true)
                ->collectShippingRates();
            return;
        }

        $shippingMethod = $this->subscriptionHelper->getAutoRefillShippingOption($quote->getStoreId());
        if(!$shippingMethod){
            throw new \Exception("No shipping method configured for auto-refills");
        }
        
        $shippingAddress->setShippingMethod($shippingMethod);

        if($freeShipping){
            $shippingAddress->setFreeShipping(true);
        }

        $shippingCostOverwrite = $this->subscriptionHelper->getAutoRefillShippingPriceOverride(($quote->getStoreId()));
        $shippingCostOverwriteNum = floatval($shippingCostOverwrite);
        $shippingCostOverwriteIsNull = $shippingCostOverwrite === null;

        if(!$shippingCostOverwriteIsNull && !$freeShipping){
            $shippingAddress->setCollectShippingRates(true)
                ->collectShippingRatesWithOverride($shippingCostOverwriteNum, $this->storeManager);
        } else {
            $shippingAddress->setCollectShippingRates(true)
                ->collectShippingRates();
        }
    }

    private function getApplicableSubscriptionPromo(
        \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
    ) {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('subscription_customer_id', $subscriptionCustomer->getId(), 'eq')
            ->addFilter('used_status', false, 'eq')
            ->addFilter('error_message', true, 'null')
            ->create();

        $subscriptionPromoList = $this->subscriptionPromoRepository->getList($searchCriteria);
        $couponCode = false;

        $mostRecentDate = Carbon::now();
        foreach ($subscriptionPromoList->getItems() as $_couponCode) {
            $createdAt = $_couponCode->getCreatedAt();
            $createdDate = Carbon::createFromTimeString($createdAt);
            if ($createdDate->lessThan($mostRecentDate)) {
                $couponCode = $_couponCode;
                $mostRecentDate = $createdDate;
            }
        }
        return $couponCode;
    }

    private function isExistingDevice($serialNumber)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('serial_number', $serialNumber, 'eq')
            ->create();

        $subscriptionDevices = $this->subscriptionDeviceRepository
            ->getList($searchCriteria)
            ->getItems();

        //This is to account for the subscription device created when passing through the API
        if($subscriptionDevices && (count($subscriptionDevices) > 1) ){
            return true;
        }

        return false;
    }

    private function couponCodeIsValid($couponCode, $customerId = null)
    {
        try {
            $this->promoHelper->couponCodeIsValid($couponCode, $customerId);
            return true;
        } catch(\Exception $e){
            return false;
        }
    }

    /**
     * 
     * Set coupon on quote if valid
     *
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerEstimateQueryInterface $subscriptionCustomerEstimateQuery
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Magento\Quote\Model\Quote $quote
     * 
     */
    protected function setCouponOnQuote(
        $subscriptionCustomerEstimateQuery, 
        $subscriptionCustomer, 
        $quote
    ){
        $couponCodes = $subscriptionCustomerEstimateQuery->getCouponCodes();
        $hasCouponCode = $couponCodes && count($couponCodes) > 0;
        $customerId = $subscriptionCustomer->getId();

        if($hasCouponCode 
            && $this->couponCodeIsValid($couponCodes[0], $customerId)){
            $quote->setCouponCode($couponCodes[0]);
        } else {
            $promo = $this->getApplicableSubscriptionPromo($subscriptionCustomer);
            if ($promo 
                && $this->couponCodeIsValid($promo->getCouponCode(), $customerId)) {
                $quote->setCouponCode($promo->getCouponCode());
            }
        }

        return $quote;
    }
}
