<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Cron;

use Vonnda\Subscription\Model\ResourceModel\SubscriptionCustomer\Collection;
use Vonnda\Subscription\Model\SubscriptionCustomer;
use Vonnda\Subscription\Model\SubscriptionCustomerRepository;
use Vonnda\Subscription\Model\SubscriptionPayment;
use Vonnda\Subscription\Model\SubscriptionPaymentRepository;
use Vonnda\Subscription\Model\SubscriptionOrder;
use Vonnda\Subscription\Model\SubscriptionOrderFactory;
use Vonnda\Subscription\Model\SubscriptionPlanRepository;
use Vonnda\Subscription\Model\SubscriptionProductRepository;
use Vonnda\Subscription\Model\SubscriptionPromoRepository;
use Vonnda\Subscription\Model\SubscriptionOrderRepository;
use Vonnda\Subscription\Helper\TimeDateHelper;
use Vonnda\Subscription\Helper\Data as SubscriptionHelper;
use Vonnda\Subscription\Helper\Logger as LoggerInterface;
use Vonnda\Subscription\Helper\StripeHelper as VonndaStripeHelper;
use Vonnda\Subscription\Helper\EmailHelper as VonndaEmailHelper;
use Vonnda\Subscription\Model\Customer\AddressFactory;
use Vonnda\TealiumTags\Helper\CreateSubscriptionOrders as TealiumHelper;
use StripeIntegration\Payments\Model\PaymentIntent;

use StripeIntegration\Payments\Model\StripeCustomerFactory;
use Carbon\Carbon;

use Magento\Framework\App\State;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Model\ProductRepository;
use Magento\Sales\Model\Order\Payment\Repository as OrderPaymentRepository;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\SalesRule\Model\RuleRepository;
use Astound\Affirm\Model\ResourceModel\Rule;
use Magento\Payment\Model\MethodList;//TODO - remove, this is for debugging
use Magento\Quote\Model\Quote\PaymentFactory;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\Sales\Model\AdminOrder\EmailSender;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Class CreateSubscriptionOrders
 */
class CreateSubscriptionOrders
{
    const INTERNATIONAL_ADDRESS_REGIONS = [
                'Armed Forces Africa',
                'Armed Forces Americas',
                'Armed Forces Canada',
                'Armed Forces Europe',
                'Armed Forces Middle East',
                'Armed Forces Pacific',
                'Guam',
                'Puerto Rico',
                'Virgin Islands'
    ];
    
    /**
     * Subscription Customer Collection
     *
     * @var \Vonnda\Subscription\Model\ResourceModel\SubscriptionCustomer\Collection $subscriptionCustomerCollection
     */
    protected $subscriptionCustomerCollection;

    /**
     * Subscription Customer Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionCustomerRepository $subscriptionCustomerRepository
     */
    protected $subscriptionCustomerRepository;

    /**
     * Subscription Customer Collection
     *
     * @var \Vonnda\Subscription\Model\SubscriptionOrderFactory $subscriptionOrder
     */
    protected $subscriptionOrder;

    /**
     * App State
     *
     * @var \Magento\Framework\App\State $state
     */
    protected $state;

    /**
     * Store Emulation
     *
     * @var \Magento\Store\Model\App\Emulation $emulation
     */
    protected $emulation;

    /**
     * Store Manager Interface
     *
     * @var \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * Customer Factory
     *
     * @var \Magento\Customer\Model\CustomerFactory $customerFactory
     */
    protected $customerFactory;

    /**
     * Address Factory
     *
     * @var \Vonnda\Subscription\Model\Customer\AddressFactory $addressFactory
     */
    protected $addressFactory;

    /**
     * Customer Repository Interface
     *
     * @var \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
     */
    protected $customerRepositoryInterface;

    /**
     * Cart Management
     *
     * @var \Magento\Quote\Api\CartManagementInterface $cartManagement
     */
    protected $cartManagement;

        /**
     * Cart Repository
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     */
    protected $cartRepository;


    /**
     * Search Criteria Builder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Vonnda Subscription Logger
     *
     * @var \Vonnda\Subscription\Helper\Logger $logger
     */
    protected $logger;

    /**
     * Product Repository
     *
     * @var \Magento\Catalog\Model\ProductRepository $productRepository
     */
    protected $productRepository;

    /**
     * Subscription Plan Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionPlanRepository $subscriptionPlanRepository
     */
    protected $subscriptionPlanRepository;

    /**
     * Subscription Product Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionProductRepository $subscriptionProductRespository
     */
    protected $subscriptionProductRespository;

    /**
     * Time Date Helper
     *
     * @var \Vonnda\Subscription\Helper\TimeDateHelper $timeDateHelper
     */
    protected $timeDateHelper;

    /**
     * Subscription Helper
     *
     * @var \Vonnda\Subscription\Helper\Data $subscriptionHelper
     */
    protected $subscriptionHelper;

    /**
     * Time Date Helper
     *
     * @var \Vonnda\Subscription\Helper\StripeHelper $vonndaStripeHelper
     */
    protected $vonndaStripeHelper;

    /**
     * Stripe Customer Factory
     *
     * @var \StripeIntegration\Payments\Model\StripeCustomerFactory $stripeCustomerFactory
     */
    protected $stripeCustomerFactory;

    /**
     * Order Payment Repository
     *
     * @var \Magento\Sales\Model\Order\Payment\Repository $orderPaymentRepository
     */
    protected $orderPaymentRepository;

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
     * Subscription Order Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionOrderRepository $subscriptionOrderRepository
     */
    protected $subscriptionOrderRepository;

    /**
     * Rule Repository
     *
     * @var \Magento\SalesRule\Model\RuleRepository $ruleRepository
     */
    protected $ruleRepository;

    /**
     * Method List
     *
     * @var \Magento\Payment\Model\MethodList $methodList
     */
    protected $methodList;

    protected $paymentFactory;

    /**
     * Order Repository
     *
     * @var \Magento\Sales\Api\OrderRepositoryInterface $orderRepository;
     */
    protected $orderRepository;

    /**
     * Tealium Helper
     *
     *
     */
    protected $tealiumHelper;

    /**
     * Email Helper
     *
     *
     */
    protected $emailHelper;

    /**
     * Quote Totals Collector
     *
     * @var TotalsCollector $totalsCollector
     */
    protected $totalsCollector;

    /**
     * Email Sender
     *
     * @var \Magento\Sales\Model\AdminOrder\EmailSender $emailSender;
     */
    protected $emailSender;

    /**
     * Checkout Session
     *
     * @var CheckoutSession $checkoutSession;
     */
    protected $checkoutSession;

    private $_last4;

    protected $paymentIntent;

    /**
     * @param Collection $subscriptionCustomerCollection
     * @param SubscriptionCustomerRepository $subscriptionCustomerRepository
     * @param SubscriptionOrderFactory $subscriptionOrder
     * @param State $state
     * @param Emulation $emulation
     * @param StoreManagerInterface $storeManagerInterface
     * @param CustomerFactory $customerFactory
     * @param AddressFactory $addressFactory
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param CartManagementInterface $cartManagement
     * @param CartRepositoryInterface $cartRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param LoggerInterface $logger
     * @param ProductRepository $productRepository
     * @param SubscriptionPlanRepository $subscriptionPlanRepository
     * @param SubscriptionProductRepository $subscriptionProductRepository
     * @param TimeDateHelper $timeDateHelper
     * @param SubscriptionHelper $subscriptionHelper
     * @param StripeCustomerFactory $stripeCustomerFactory
     * @param OrderPaymentRepository $orderPaymentRepository
     * @param SubscriptionPaymentRepository $subscriptionPaymentRepository
     * @param SubscriptionPromoRepository $subscriptionPromoRepository
     * @param SubscriptionOrderRepository $subscriptionOrderRepository
     * @param VonndaStripeHelper $vonndaStripeHelper
     * @param RuleRepository $ruleRepository
     * @param MethodList $methodList
     * @param PaymentFactory $paymentFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param TealiumHelper $tealiumHelper
     * @param VonndaEmailHelper $emailHelper
     * @param TotalsCollector $totalsCollector
     * @param EmailSender $emailSender
     * @param CheckoutSession $checkoutSession
     * @param PaymentIntent $paymentIntent
     */
    public function __construct(
        Collection $subscriptionCustomerCollection,
        SubscriptionCustomerRepository $subscriptionCustomerRepository,
        SubscriptionOrderFactory $subscriptionOrder,
        State $state,
        Emulation $emulation,
        StoreManagerInterface $storeManagerInterface,
        CustomerFactory $customerFactory,
        AddressFactory $addressFactory,
        CustomerRepositoryInterface $customerRepositoryInterface,
        CartManagementInterface $cartManagement,
        CartRepositoryInterface $cartRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger,
        ProductRepository $productRepository,
        SubscriptionPlanRepository $subscriptionPlanRepository,
        SubscriptionProductRepository $subscriptionProductRepository,
        TimeDateHelper $timeDateHelper,
        SubscriptionHelper $subscriptionHelper,
        StripeCustomerFactory $stripeCustomerFactory,
        OrderPaymentRepository $orderPaymentRepository,
        SubscriptionPaymentRepository $subscriptionPaymentRepository,
        SubscriptionPromoRepository $subscriptionPromoRepository,
        SubscriptionOrderRepository $subscriptionOrderRepository,
        VonndaStripeHelper $vonndaStripeHelper,
        RuleRepository $ruleRepository,
        MethodList $methodList,
        PaymentFactory $paymentFactory,
        OrderRepositoryInterface $orderRepository,
        TealiumHelper $tealiumHelper,
        VonndaEmailHelper $emailHelper,
        TotalsCollector $totalsCollector,
        EmailSender $emailSender,
        CheckoutSession $checkoutSession,
        PaymentIntent $paymentIntent
    ){
        $this->subscriptionCustomerCollection = $subscriptionCustomerCollection;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->subscriptionOrder = $subscriptionOrder;
        $this->state = $state;
        $this->emulation = $emulation;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->customerFactory = $customerFactory;
        $this->addressFactory = $addressFactory;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->cartManagement = $cartManagement;
        $this->cartRepository = $cartRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->subscriptionPlanRepository = $subscriptionPlanRepository;
        $this->subscriptionProductRepository = $subscriptionProductRepository;
        $this->subscriptionHelper = $subscriptionHelper;
        $this->timeDateHelper = $timeDateHelper;
        $this->stripeCustomerFactory = $stripeCustomerFactory;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
        $this->subscriptionPromoRepository = $subscriptionPromoRepository;
        $this->subscriptionOrderRepository = $subscriptionOrderRepository;
        $this->vonndaStripeHelper = $vonndaStripeHelper;
        $this->ruleRepository = $ruleRepository;
        $this->methodList = $methodList;
        $this->paymentFactory = $paymentFactory;
        $this->orderRepository = $orderRepository;
        $this->tealiumHelper = $tealiumHelper;
        $this->emailHelper = $emailHelper;
        $this->totalsCollector = $totalsCollector;
        $this->emailSender = $emailSender;
        $this->checkoutSession = $checkoutSession;
        $this->paymentIntent = $paymentIntent;
    }

    /**
     * Iterate through subscription customers and create orders
     *
     * @return $this
     * @throws \Exception
     */
    public function execute() {
        $this->log("Vonnda subscriptions cron started");

        $todaysOrders = $this->getTodaysOrders();
        if(count($todaysOrders->getItems()) == 0){
            $this->log("No orders found for today");
        } else {
            $this->processSubscriptions($todaysOrders);
        }

        $this->turnOffExpiredSubscriptionsWithExpiredPayment();

        $firstRetryOrders = $this->getRetryOrders((int)$this->subscriptionHelper->getCronConfig('first_retry_days'));
        if(count($firstRetryOrders->getItems()) == 0){
            $this->log("No first retry orders found for today");
        } else {
            $this->processSubscriptions($firstRetryOrders, 2);
        }

        $secondRetryOrders = $this->getRetryOrders((int)$this->subscriptionHelper->getCronConfig('second_retry_days'));
        if(count($secondRetryOrders->getItems()) == 0){
            $this->log("No second retry orders found for today");
        } else {
            $this->processSubscriptions($secondRetryOrders, 3);
        }

        $thirdRetryOrders = $this->getRetryOrders((int)$this->subscriptionHelper->getCronConfig('third_retry_days'));
        if(count($thirdRetryOrders->getItems()) == 0){
            $this->log("No third retry orders found for today");
        } else {
            $this->processSubscriptions($thirdRetryOrders, 4);
        }

        $this->log("Vonnda subscriptions cron finished");
    }

    public function processSubscriptions($subscriptionCustomers, $attemptNumber = 1)
    {
        if($attemptNumber > 1){
            $this->log("Processing retry orders attempt number " . $attemptNumber);
        }
        foreach ($subscriptionCustomers->getItems() as $subscriptionCustomer) {
            if($this->checkForSuccessfulOrder($subscriptionCustomer)){
                continue;
            }
            if(($attemptNumber > 1) && $this->checkForRetryOrder($subscriptionCustomer)){
                continue;
            }
            $subscriptionCustomerId = $subscriptionCustomer->getId();
            $this->log("Processing subscription customer ID:" . $subscriptionCustomer->getId());
            try {
                $subscriptionCustomer = $this->subscriptionCustomerRepository->getById($subscriptionCustomerId);
                $subscriptionPlan = $this->subscriptionPlanRepository
                    ->getById($subscriptionCustomer->getSubscriptionPlanId());

                //Apply basic validation to subscription
                $subscriptionCustomerIsExpired = $this->isSubscriptionExpired($subscriptionCustomer, $subscriptionPlan);
                if($subscriptionCustomerIsExpired){
                    $this->handleExpiredSubscriptionCustomer($subscriptionCustomer);
                    $this->subscriptionCustomerRepository->save($subscriptionCustomer);
                    continue;
                }

                try {
                    $this->validateSubscriptionCustomer($subscriptionCustomer, $subscriptionPlan);
                } catch(\Exception $e){
                    $subscriptionCustomer->setStatus(SubscriptionCustomer::PROCESSING_ERROR_STATUS)
                        ->setErrorMessage($e->getMessage());
                    $this->subscriptionCustomerRepository->save($subscriptionCustomer);
                    $subscriptionOrderId = $this->createSubscriptionOrder(
                        $subscriptionCustomerId,
                        null,
                        SubscriptionOrder::ERROR_STATUS,
                        $e->getMessage()
                    );
                    continue;
                }

                $salesOrder = $this->createSalesOrder($subscriptionCustomer, $subscriptionPlan, $attemptNumber);
                $this->log("New sales order created ID:" . $salesOrder['orderId']);

                $this->resetSubscriptionCustomerFields($subscriptionCustomer);
                $subscriptionOrderId = $this->createSubscriptionOrder(
                    $subscriptionCustomerId,
                    $salesOrder['orderId'],
                    SubscriptionOrder::SUCCESS_STATUS,
                    null
                );

                $subscriptionPlan = $this->transitionSubscriptionPlan($subscriptionCustomer, $subscriptionPlan);
                $this->updateNextAndLastOrderDate($subscriptionCustomer, $subscriptionPlan);

                $usedSubscriptionPromo = $salesOrder['usedSubscriptionPromo'];
                if($usedSubscriptionPromo){
                    $usedSubscriptionPromo->setSubscriptionOrderId($subscriptionOrderId)
                        ->setUsedStatus(true);
                    $this->subscriptionPromoRepository->save($usedSubscriptionPromo);
                }
                //Clear Error State if order is succsefully created on retry
                if($attemptNumber > 1){
                    $subscriptionCustomer->setStatus(SubscriptionCustomer::AUTORENEW_ON_STATUS)
                        ->setErrorMessage('');
                }
                $this->subscriptionCustomerRepository->save($subscriptionCustomer);
                $this->log('Subscription order created ID:' . $subscriptionOrderId);
            } catch(\Exception $e){
                $this->log($e->getMessage());
                $this->subscriptionCustomerRepository->save($subscriptionCustomer);
            }
        }

        return $this;
    }

    /**
     * Get all of todays active orders with next_order set to today
     *
     * @param void
     * @return \Vonnda\Subscription\Model\ResourceModel\SubscriptionCustomer\Collection
     *
     */
    public function getTodaysOrders()
    {
        $from = Carbon::now()->setTimezone('America/Los_Angeles')->startOfDay()->toDateTimeString();
        $to = Carbon::now()->addDay()->setTimezone('America/Los_Angeles')->startOfDay()->toDateTimeString();
        $this->log("Processing active subscriptions from " . $from . " to " . $to);

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('state', SubscriptionCustomer::ACTIVE_STATE ,'eq')
            ->addFilter('next_order',$from,'gteq')
            ->addFilter('next_order',$to,'lteq')
            ->create();

        return $this->subscriptionCustomerRepository->getList($searchCriteria);
    }

    /**
     * Get all rejected orders offset by a specific day
     *
     * @param void
     * @return \Vonnda\Subscription\Model\ResourceModel\SubscriptionCustomer\Collection
     *
     */
    public function getRetryOrders($dayInterval)
    {
        $from = Carbon::now()->subDays($dayInterval)->setTimezone('America/Los_Angeles')->startOfDay()->toDateTimeString();
        $to = Carbon::now()->subDays($dayInterval - 1)->setTimezone('America/Los_Angeles')->startOfDay()->toDateTimeString();
        $this->log("Processing declined subscriptions from " . $from . " to " . $to);

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('status', SubscriptionCustomer::PAYMENT_INVALID_STATUS ,'eq')
            ->addFilter('next_order',$from,'gteq')
            ->addFilter('next_order',$to,'lteq')
            ->create();

        return $this->subscriptionCustomerRepository->getList($searchCriteria);
    }

    /**
     * Set any subscription that have an old next order date to autorenew_off
     *
     * @param void
     * @return void
     *
     */
    public function turnOffExpiredSubscriptionsWithExpiredPayment()
    {
        $this->log("Turning off expired subscriptions with expired payments");
        $today = Carbon::now()->setTimezone('America/Los_Angeles')->startOfDay()->toDateTimeString();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('status', SubscriptionCustomer::PAYMENT_EXPIRED_STATUS ,'eq')
            ->addFilter('next_order',$today,'lteq')
            ->create();

        $subscriptionList = $this->subscriptionCustomerRepository->getList($searchCriteria);
        $subcriptions = $subscriptionList->getItems();
        if(count($subcriptions) == 0){
            $this->log("No subscriptions with expired payments to be processed");
        }
        $counter = 0;
        foreach($subcriptions as $subcription){
            $subcription->setStatus(SubscriptionCustomer::AUTORENEW_OFF_STATUS);
            $counter++;
        }

        $this->log($counter . " subscriptions set to AUTORENEW_OFF");
    }

    /**
     * Check if there was already a successful order for that day
     *
     * @param $subscriptionCustomer
     * @return boolean
     *
     */
    public function checkForSuccessfulOrder($subscriptionCustomer)
    {
        $from = Carbon::now()->setTimezone('America/Los_Angeles')->startOfDay()->toDateTimeString();
        $to = Carbon::now()->addDay()->setTimezone('America/Los_Angeles')->startOfDay()->toDateTimeString();
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('status', SubscriptionOrder::SUCCESS_STATUS ,'eq')
            ->addFilter('subscription_customer_id', $subscriptionCustomer->getId())
            ->addFilter('created_at',$from,'gteq')
            ->addFilter('created_at',$to,'lteq')
            ->create();

        $orders = $this->subscriptionOrderRepository->getList($searchCriteria);
        $hasOrder = (count($orders->getItems()) > 0);

        if($hasOrder){
            return true;
        }

        return false;
    }

    /**
     * So we don't continually repeat retry orders
     *
     * @param $subscriptionCustomer
     * @return boolean
     *
     */
    public function checkForRetryOrder($subscriptionCustomer)
    {
        $from = Carbon::now()->setTimezone('America/Los_Angeles')->startOfDay()->toDateTimeString();
        $to = Carbon::now()->addDay()->setTimezone('America/Los_Angeles')->startOfDay()->toDateTimeString();
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('subscription_customer_id', $subscriptionCustomer->getId())
            ->addFilter('created_at',$from,'gteq')
            ->addFilter('created_at',$to,'lteq')
            ->create();

        $orders = $this->subscriptionOrderRepository->getList($searchCriteria);
        $hasOrder = (count($orders->getItems()) > 0);

        if($hasOrder){
            return true;
        }

        return false;
    }

    /**
     * Create a sales order given a subscription customer and subscription plan
     *
     * @param \Vonnda\Subscription\Model\SubscriptionCustomer $subscriptionCustomer
     * @param \Vonnda\Subscription\Model\SubscriptionPlan $subscriptionPlan
     * @return int $orderId | void
     * @throws \Exception
     *
     */
    public function createSalesOrder(
        \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer,
        \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface $subscriptionPlan,
        $attemptNumber
    ){
        try {
            $storeId = $subscriptionPlan->getStoreId();
            $this->emulation->startEnvironmentEmulation($storeId, 'adminhtml', true);
            $this->_last4 = '';
            $quote = $this->initializeQuote($subscriptionCustomer);

            $orderInfo = [];
            if($this->subscriptionHasFreeOrders($subscriptionCustomer, $subscriptionPlan)){
                $this->log("Subscription eligible for free shipment");
                $this->setProductsOnQuote($quote, $subscriptionPlan, true);
                $this->setAddressesOnQuote($quote, $subscriptionCustomer);
                $this->setShippingMethodOnQuote($quote, $subscriptionCustomer, true);
                $this->setZeroPaymentOnQuote($quote, $subscriptionCustomer);
                $orderInfo["usedSubscriptionPromo"] = false;
            } else {
                $this->setProductsOnQuote($quote, $subscriptionPlan);
                $this->setAddressesOnQuote($quote, $subscriptionCustomer);
                $this->setShippingMethodOnQuote($quote, $subscriptionCustomer);
                $orderInfo["usedSubscriptionPromo"]= $this->applySubscriptionPromoToQuote($quote, $subscriptionCustomer);
                $this->setPaymentOnQuote($quote, $subscriptionCustomer, $attemptNumber);
            }
            $orderId = $this->submitAndFinishQuote($quote, $subscriptionCustomer, $attemptNumber);
            $this->emulation->stopEnvironmentEmulation();
            $orderInfo["orderId"] = $orderId;
            return $orderInfo;

        } catch(\Exception $e){
            $this->emulation->stopEnvironmentEmulation();
            $this->log('Sales order failed for subscription customer ID:' . $subscriptionCustomer->getId());

            //We want to retain any error message that was set earlier
            if($subscriptionCustomer->getState() != SubscriptionCustomer::ERROR_STATE &&  $subscriptionCustomer->getState() != SubscriptionCustomer::INACTIVE_STATE){
                $subscriptionCustomer->setStatus(SubscriptionCustomer::PROCESSING_ERROR_STATUS)
                    ->setErrorMessage($e->getMessage());
                $this->subscriptionCustomerRepository->save($subscriptionCustomer);
            }

            $subscriptionOrderId = $this->createSubscriptionOrder($subscriptionCustomer->getId(),
                                                                  null,
                                                                  SubscriptionOrder::ERROR_STATUS,
                                                                  $e->getMessage());

            //We don't want the couponCode to be used if the order didn't go through
            $couponCode = $this->getApplicableSubscriptionPromo($subscriptionCustomer);
            if($couponCode){
                $couponCode->setUsedAt(null)
                           ->setSubscriptionOrderId(null);
                $this->subscriptionPromoRepository->save($couponCode);
            }

            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Create a subscription order and return id
     *
     * @param int $subscriptionCustomerId
     * @param int | null $orderId
     * @param string $status
     * @param string | null $errorMessage
     * @return int
     * @throws \Exception
     *
     */
    protected function createSubscriptionOrder(
        int $subscriptionCustomerId,
        $orderId,
        string $status,
        $errorMessage
    ){
        try {
            $subscriptionOrder = $this->subscriptionOrder->create()
                                      ->setSubscriptionCustomerId($subscriptionCustomerId)
                                      ->setOrderId($orderId)
                                      ->setStatus($status)
                                      ->setErrorMessage($errorMessage)
                                      ->save();
            return $subscriptionOrder->getId();
        } catch(\Exception $e){
            $this->log($e);
            throw new \Exception('Failure creating subscription order');
        }
    }

    /**
     * Build quote object
     *
     * @param void
     * @return \Magento\Quote\Model\Quote $quote
     *
     */
    protected function initializeQuote($subscriptionCustomer)
    {
        if($subscriptionCustomer->getCustomerId()){
            $customer = $this->customerRepositoryInterface->getById($subscriptionCustomer->getCustomerId());
        } else {
            $subscriptionCustomer->setStatus(SubscriptionCustomer::PROCESSING_ERROR_STATUS)
                                 ->setErrorMessage("No customer associated with subscription ID: " . $subscriptionCustomer->getId());
            $this->subscriptionCustomerRepository->save($subscriptionCustomer);
            throw new \Exception("No customer associated with subscription ID: " . $subscriptionCustomer->getId());
        }
        try {
            $cart = $this->cartManagement->getCartForCustomer($subscriptionCustomer->getCustomerId());
            if($cart){
                $cart->setIsActive(0)->save();
            }
        } catch(\Exception $e){
            //This will throw a no such identity error
        }
        $cartId = $this->cartManagement->createEmptyCartForCustomer($subscriptionCustomer->getCustomerId());
        $cart = $this->cartManagement->getCartForCustomer($subscriptionCustomer->getCustomerId());
        return $cart;
    }

    /**
     * Add products to quote
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface $subscriptionPlan
     * @return void
     *
     */
    protected function setProductsOnQuote(
        $quote,
        \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface $subscriptionPlan,
        $freeOrder = false
    ){
        $subscriptionPlanId = $subscriptionPlan->getId();
        $subscriptionProducts = $this->subscriptionProductRepository
            ->getSubscriptionProductsByPlanId($subscriptionPlanId);

        foreach($subscriptionProducts->getItems() as $subscriptionProduct){
            $product = $this->productRepository->getById($subscriptionProduct->getProductId());
            $item = $quote->addProduct($product, $subscriptionProduct->getQty());
            if($freeOrder){
                $this->log("Applying price override for free order to product ID:" . $product->getId() );
                $item->setCustomPrice(0);
                $item->setOriginalCustomPrice(0);
                $item->save();
            } else {
                $priceOverride = $subscriptionProduct->getPriceOverride();
                if($priceOverride){
                    $this->log("Applying price override ($" . $priceOverride . " to product ID:" . $product->getId() );
                    $item->setCustomPrice($priceOverride);
                    $item->setOriginalCustomPrice($priceOverride);
                    $item->save();
                }
            }
        }
    }

    /**
     * Configure addresses and correct shipping for quote
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @return void
     *
     */
    protected function setAddressesOnQuote(
        $quote,
        \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
    ){
        $address = $this->addressFactory->create()->load($subscriptionCustomer->getShippingAddressId());

        $subscriptionPayment = $subscriptionCustomer->getPayment();

        if($subscriptionPayment && $subscriptionPayment->getBillingAddressId()){
            $billingAddress = $this->addressFactory->create()->load($subscriptionPayment->getBillingAddressId());
            $quote->getBillingAddress()->addData($billingAddress->getData());
        } else {
            $quote->getBillingAddress()->addData($address->getData());
        }
        $quote->getShippingAddress()->addData($address->getData());
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
        $addressIsInternational = $this->isAddressInternational($shippingAddress);

        if($addressIsInternational){
            $shippingMethod = $this->subscriptionHelper->getAutoRefillShippingOptionInternational($quote->getStoreId());
        } else {
            $shippingMethod = $this->subscriptionHelper->getAutoRefillShippingOption($quote->getStoreId());
        }

        if(!$shippingMethod){
            throw new \Exception("No shipping method configured for auto-refills");
        }
        $shippingAddress->setCollectShippingRates(true);
        $this->totalsCollector->collectAddressTotals($quote, $shippingAddress);
        $this->log('SHIPPING METHOD - '. $shippingMethod);
        $shippingAddress->setShippingMethod($shippingMethod);

        $shippingPriceOverride = floatval($this->subscriptionHelper->getAutoRefillShippingPriceOverride(($quote->getStoreId())));

        if($subscriptionCustomer->getShippingMethodOverwrite()){
            $this->log("Applying shipping overwrite " . $subscriptionCustomer->getShippingMethodOverwrite());
            $shippingAddress->setShippingMethod($subscriptionCustomer->getShippingMethodOverwrite());
            $shippingPriceOverride = false;  //Config value intended only for cron config shipping method
            $shouldApplyCostOverwrite = $subscriptionCustomer->getShippingCostOverwrite()
                || $this->subscriptionHelper->isZeroNumber($subscriptionCustomer->getShippingCostOverwrite());
            if($shouldApplyCostOverwrite){
                $shippingPriceOverride = $subscriptionCustomer->getShippingCostOverwrite();
                $shippingAddress->setCollectShippingRates(true)
                    ->collectShippingRatesWithOverride($shippingPriceOverride, $this->storeManagerInterface);
                return;
            }

            $shippingAddress->setCollectShippingRates(true)
                ->collectShippingRates();
            return;
        }

        if($addressIsInternational){
            $shippingCostOverwrite = $this->subscriptionHelper->getAutoRefillShippingPriceOverrideInternational(($quote->getStoreId()));
        } else {
            $shippingCostOverwrite = $this->subscriptionHelper->getAutoRefillShippingPriceOverride(($quote->getStoreId()));
        }
        
        $shippingCostOverwriteNum = floatval($shippingCostOverwrite);
        $shippingCostOverwriteIsNull = $shippingCostOverwrite === null;

        if(!$shippingCostOverwriteIsNull){
            $shippingAddress->setCollectShippingRates(true)
                ->collectShippingRatesWithOverride($shippingCostOverwriteNum, $this->storeManagerInterface);
        } else {
            $shippingAddress->setCollectShippingRates(true)
                ->collectShippingRates();
        }
    }

    /**
     * Check if address is international
     *
     * @param $address
     * @return boolean
     * 
     */
    public function isAddressInternational(
        $address
    ){
        $isUS = $address->getCountryId() === "US";
        $isCanada = $address->getCountryId() === "CA";

        if(in_array($address->getRegion(), self::INTERNATIONAL_ADDRESS_REGIONS) && $isUS){
            return true;
        } else if(!$isUS && !$isCanada){
            return true;
        }

        return false;
    }

    /**
     * Configure payment for quote
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @param int $attemptNumber
     * @return void
     *
     */
    protected function setPaymentOnQuote(
        $quote,
        \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer,
        int $attemptNumber
    )
    {
        $subscriptionPayment = $subscriptionCustomer->getPayment();
        if ($subscriptionPayment) {
            try {
                $this->handleStripeCustomer($quote, $subscriptionPayment, $subscriptionCustomer, $attemptNumber);
            } catch(\Exception $e){
                $this->log("An error occurred while trying to process a stripe customer");
                $this->log($e->getMessage());
                //Re-throw so we capture it in vonnda_subscription_order
                throw new \Exception($e->getMessage());
            }
        }
    }

    /**
     * Configure payment for quote for free order
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return void
     *
     */
    protected function setZeroPaymentOnQuote(
        $quote
    )
    {
        $payment = $quote->getPayment();
        $payment->setMethod('free');
        $quote->setInventoryProcessed(false);
        $quote->save();
        $quote->collectTotals()->save();
    }

    /**
     * Submit quote and finish
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @param int $attemptNumber
     * @return int $orderId
     *
     */
    protected function submitAndFinishQuote(
        $quote,
        \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer,
        int $attemptNumber)
    {
        try {
            $subscriptionPayment = $subscriptionCustomer->getPayment();

            $emailTemplateVariables = [
                "quote" => $quote,
                "subscriptionCustomer" => $subscriptionCustomer,
                "subscriptionPlan" => $subscriptionCustomer->getSubscriptionPlan(),
                "attemptNumber" => $attemptNumber
            ];
            $this->emailHelper->sendChargeAttemptEmail($quote->getCustomer(), $emailTemplateVariables);
            $this->log('submitAndFinishQuote - BEFORE TEALIUM CHARGE ATTEMPT - ');
            $this->tealiumHelper->createAutoRenewalChargeAttemptEvent($quote, $subscriptionCustomer, $attemptNumber);
            $this->log('submitAndFinishQuote - AFTER TEALIUM - BEFORE EMULATE - ');
            $this->checkoutSession->setHideTealiumEmailPreferences(true);
            $orderId = $this->state->emulateAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML, [$this->cartManagement, 'placeOrder'], [$quote->getId()]);
            $this->log('submitAndFinishQuote - AFTER EMULATE ORDER BEFORE ORDER - ');
            $order = $this->orderRepository->get($orderId);
            $this->log('submitAndFinishQuote - AFTER ORDER BEFORE TEALIUM CHARGE SUCCESS - ');
            $this->tealiumHelper->createAutoRenewalChargeSuccessEvent($order, $quote, $subscriptionCustomer, $attemptNumber, $this->_last4);
            $this->log('submitAndFinishQuote - AFTER TEALIUM CHARGE SUCCESS BEFORE SET PARENT ID - ');
            $order->setParentOrderId($subscriptionCustomer->getParentOrderId());
            $this->orderRepository->save($order);
            $this->emailSender->send($order);
            return $orderId;
            //TODO - match more closely the exception if possible
        } catch(\Exception $e){
            $this->paymentIntent->destroy($quote->getId(),true);
            $quote->setIsActive(0)->save();
            $this->tealiumHelper->createAutoRenewalChargeFailureEvent($quote, $subscriptionCustomer, $attemptNumber, $this->_last4, $e->getMessage());
            $this->log('submitAndFinishQuote - EXCEPTION - '.$e->getMessage());
            $this->notifyCustomerOfError($quote, $subscriptionCustomer, $attemptNumber);
            if($attemptNumber === 4){
                $subscriptionPayment->setStatus(SubscriptionPayment::INVALID_STATUS)->save();
                $subscriptionCustomer->setStatus(SubscriptionCustomer::AUTORENEW_OFF_STATUS)
                    ->setErrorMessage("Order was declined - 4th attempt - subscription turned off");
                $this->subscriptionCustomerRepository->save($subscriptionCustomer);
                throw new \Exception('Order was declined - 4th attempt - subscription turned off');
            } else {
                $subscriptionCustomer->setStatus(SubscriptionCustomer::PAYMENT_INVALID_STATUS)
                    ->setErrorMessage("Order was declined");
                $this->subscriptionCustomerRepository->save($subscriptionCustomer);
                throw new \Exception('Order was declined');
            }
        }

    }

    /**
     * Modify the last_order and next_order dates after order is placed
     *
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @param \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface $subscriptionPlan
     * @return void
     *
     */
    protected function updateNextAndLastOrderDate(
        \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer,
        \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface $subscriptionPlan
    ){
        if($subscriptionCustomer->getStatus() === SubscriptionCustomer::AUTORENEW_COMPLETE_STATUS){
            return $subscriptionCustomer->setLastOrder(Carbon::now()->toDateTimeString());
        }

        $frequency = $subscriptionPlan->getFrequency();
        $frequencyUnits = $subscriptionPlan->getFrequencyUnits();
        $nextOrderDate = $this->timeDateHelper->getNextDateFromFrequency($frequency, $frequencyUnits);
        $subscriptionCustomer->setLastOrder(Carbon::now()->toDateTimeString())
            ->setNextOrder($nextOrderDate);
    }

    /**
     * Transition or Expire Subscription Plan based on plan duration
     *
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @param \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface $subscriptionPlan
     * @return \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface $subscriptionPlan
     *
     */
    protected function transitionSubscriptionPlan(
        \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer,
        \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface $subscriptionPlan
    ){
        if(!$subscriptionPlan->getDuration()){
            return $subscriptionPlan;
        }

        $numSuccessFullSubscriptionOrders = $this->countSuccessFullSubscriptionOrders($subscriptionCustomer);
        if($numSuccessFullSubscriptionOrders == $subscriptionPlan->getDuration()
            && $subscriptionPlan->getFallbackPlan()){
                $newSubscriptionPlan = $this->subscriptionPlanRepository->getByIdentifier($subscriptionPlan->getFallbackPlan());
                if(!$newSubscriptionPlan){
                    throw new \Exception("Subscription plan with identifier '" . $subscriptionPlan->getFallbackPlan() .
                        "' not found, could not transition subscription ID:" . $subscriptionCustomer->getId());
                }
                $subscriptionCustomer->setSubscriptionPlan($newSubscriptionPlan);
                $this->subscriptionCustomerRepository->save($subscriptionCustomer);
                return $newSubscriptionPlan;
        }elseif($numSuccessFullSubscriptionOrders == $subscriptionPlan->getDuration()){
            $subscriptionCustomer->setStatus(SubscriptionCustomer::AUTORENEW_COMPLETE_STATUS)
                ->setNextOrder(null);
            $this->subscriptionCustomerRepository->save($subscriptionCustomer);
            return $subscriptionPlan;
        } else {
            return $subscriptionPlan;
        }
    }

    /**
     * Reset error messages and shipping method/cost overwrites
     *
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @return void
     *
     */
    protected function resetSubscriptionCustomerFields(
        \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
    ){
        $subscriptionCustomer->setErrorMessage(null)
            ->setShippingMethodOverwrite(null)
            ->setShippingCostOverwrite(null);
    }

    /**
     * Handle any stripe customer - we attempt to use the same card from the original order
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Vonnda\Subscription\Api\Data\SubscriptionPaymentInterface $subscriptionPayment
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @param int $attemptNumber
     * @return void
     * @throws \Exception
     */
    protected function handleStripeCustomer(
        $quote,
        \Vonnda\Subscription\Api\Data\SubscriptionPaymentInterface $subscriptionPayment,
        \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer,
        $attemptNumber
    ){
        $this->state->emulateAreaCode('adminhtml', function ($quote, $subscriptionPayment, $subscriptionCustomer, $attemptNumber) {
            if ($subscriptionPayment->getStatus() == SubscriptionPayment::VALID_STATUS) {
                $stripeCustomerModel = $this->stripeCustomerFactory->create();
                $stripeCustomerModel->load($subscriptionCustomer->getCustomerId(), 'customer_id');
                $cards = $stripeCustomerModel->getCustomerCards();
                if (!is_array($cards) || count($cards) == 0) {
                    $this->notifyCustomerOfError($quote, $subscriptionCustomer, $attemptNumber);
                    if($attemptNumber === 4){
                        $subscriptionPayment->setStatus(SubscriptionPayment::INVALID_STATUS)->save();
                        $subscriptionCustomer->setStatus(SubscriptionCustomer::AUTORENEW_OFF_STATUS)
                            ->setErrorMessage("Customer has no saved stripe cards - 4th attempt - subscription turned off");
                        throw new \Exception('Customer has no saved stripe cards - 4th attempt - subscription turned off');
                    } else {
                        $subscriptionCustomer->setStatus(SubscriptionCustomer::PAYMENT_INVALID_STATUS)
                            ->setErrorMessage("Customer has no saved stripe cards");
                        throw new \Exception('Customer has no saved stripe cards');
                    }
                }
                $data = [];
                foreach ($cards as $card) {
                    if ($subscriptionPayment->getPaymentCode() == $card->id) {
                        if ($this->cardNotExpired($card->exp_month, $card->exp_year)) {
                            $this->_last4 = $card->last4;
                            $data['cc_saved'] = $card->id . ':' . $card->brand . ':' . $card->last4;
                            break;
                        } else {
                            $this->notifyCustomerOfError($quote, $subscriptionCustomer, $attemptNumber);
                            if ($attemptNumber === 4) {
                                $subscriptionPayment->setStatus(SubscriptionPayment::INVALID_STATUS)->save();
                                $subscriptionCustomer->setStatus(SubscriptionCustomer::AUTORENEW_OFF_STATUS)
                                    ->setErrorMessage("Saved card expired - 4th attempt - subscription turned off");
                                throw new \Exception('Saved card expired - 4th attempt - subscription turned off');
                            } else {
                                $subscriptionCustomer->setStatus(SubscriptionCustomer::PAYMENT_EXPIRED_STATUS)
                                    ->setErrorMessage("Saved card expired");
                                throw new \Exception('Saved card expired');
                            }
                        }
                    }
                }
                if (empty($data)) {
                    $this->notifyCustomerOfError($quote, $subscriptionCustomer, $attemptNumber);
                    if ($attemptNumber === 4) {
                        $subscriptionPayment->setStatus(SubscriptionPayment::INVALID_STATUS)->save();
                        $subscriptionCustomer->setStatus(SubscriptionCustomer::AUTORENEW_OFF_STATUS)
                            ->setErrorMessage("No match for saved card - 4th attempt - subscription turned off");
                        throw new \Exception('No match for saved card - 4th attempt - subscription turned off');
                    } else {
                        $subscriptionCustomer->setStatus(SubscriptionCustomer::PAYMENT_INVALID_STATUS)
                            ->setErrorMessage("No match for saved card");
                        throw new \Exception('No match for saved card');
                    }
                }
                $quote->collectTotals();
                $data['amount'] = $quote->getGrandTotal();
                $data['method'] = 'stripe_payments';
                $data['customer'] = $stripeCustomerModel->getStripeId();
                $quote->getPayment()->importData($data);
                //The method used in Stripe's create charge API seems to wipe the customer_stripe_id
                $quote->getPayment()->setAdditionalInformation('customer_stripe_id', $data['customer']);
                $quote->setInventoryProcessed(false);
                $quote->save();
            } else {
                $this->notifyCustomerOfError($quote, $subscriptionCustomer, $attemptNumber);
                if ($attemptNumber === 4) {
                    $subscriptionCustomer->setStatus(SubscriptionCustomer::AUTORENEW_OFF_STATUS)
                        ->setErrorMessage("Associated subscription payment invalid - 4th attempt - subscription turned off");
                    throw new \Exception('Associated subscription payment invalid - 4th attempt - subscription turned off');
                } else {
                    $subscriptionCustomer->setStatus(SubscriptionCustomer::PAYMENT_INVALID_STATUS)
                        ->setErrorMessage("Associated subscription payment invalid");
                    throw new \Exception('Associated subscription payment invalid');
                }
            }
        }, [$quote, $subscriptionPayment, $subscriptionCustomer, $attemptNumber]);
    }

    /**
     * Check expiration date
     *
     * @param int $month
     * @param int $year
     * @return boolean
     *
     */
    protected function cardNotExpired($month, $year)
    {
        return !$this->timeDateHelper->isCardExpired($month, $year);
    }

    /**
     * Return a promo code if any applies
     *
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @return false|\Vonnda\Subscription\Model\SubscriptionPromo
     *
     */
    protected function getApplicableSubscriptionPromo(
        \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
    ){
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('subscription_customer_id', $subscriptionCustomer->getId(), 'eq')
            ->addFilter('used_status', false, 'eq')
            ->addFilter('error_message', true, 'null')
            ->create();

        $subscriptionPromoList = $this->subscriptionPromoRepository->getList($searchCriteria);
        $couponCode = false;

        $mostRecentDate = Carbon::now();
        foreach($subscriptionPromoList->getItems() as $_couponCode){
            $createdAt = $_couponCode->getCreatedAt();
            $createdDate = Carbon::createFromTimeString($createdAt);
            if($createdDate->lessThan($mostRecentDate)){
                $couponCode = $_couponCode;
                $mostRecentDate = $createdDate;
            }
        }

        return $couponCode;
    }

    /**
     * Apply subscription promo to quote
     *
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @param \Magento\Quote\Model\Quote $quote
     * @return void
     *
     */
    //TODO - do we still want to go through with it if no promos work?
    protected function applySubscriptionPromoToQuote(
        $quote,
        \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
    ){
        $promo = $this->getApplicableSubscriptionPromo($subscriptionCustomer);
        if($promo){
            $this->log("Applying the sales rule to quote couponCode: " . $promo->getCouponCode());
            try {
                $quote->setCouponCode($promo->getCouponCode())->save();
            } catch(\Exception $e){
                //If there was an error applying the coupon we mark it here and try again
                $this->log("Error adding coupon to quote");
                $this->log($e->getMessage());
                $promo->setErrorMessage($e->getMessage());
                $this->subscriptionPromoRepository->save($promo);
                if(true){//We could use a config for this
                    return $this->applySubscriptionPromoToQuote($quote, $subscriptionCustomer);
                }
            }
            //We don't change the status until the order has actually gone though
            $promo->setUsedAt(Carbon::now()->toDateTimeString())
                    ->setErrorMessage(null); //Clear the error if one was attempted before
            return $promo;

        }
        return false;
    }

    /**
     * Check $subscriptionCustomer for required fields
     *
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @return void
     *
     */
    protected function validateSubscriptionCustomer(
        \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer,
        \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface $subscriptionPlan
    ){
        if(!$subscriptionCustomer->getShippingAddressId()){
            throw new \Exception('Subscription customer must have a shipping address id');
        }

        if(!$subscriptionCustomer->getCustomerId()){
            throw new \Exception('Subscription customer must have a valid customer id');
        }

        $subscriptionPayment = $subscriptionCustomer->getPayment();

        if(
            !$subscriptionPayment &&
            ($subscriptionPlan->getPaymentRequiredForFree() || !$this->subscriptionHasFreeOrders($subscriptionCustomer, $subscriptionPlan))
        ){
            throw new \Exception('Subscription customer must have an associated payment method');
        }

        $subscriptionProducts = $this->subscriptionProductRepository
            ->getSubscriptionProductsByPlanId($subscriptionCustomer->getSubscriptionPlanId());

        if($subscriptionProducts->getTotalCount() == 0){
            throw new \Exception('No products associated with subscription plan ID: ' . $subscriptionCustomer->getSubscriptionPlanId());
        }
    }

    /**
     * Count all successfull subscription orders for a subscription customer
     *
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @return int
     *
     */
    protected function countSuccessFullSubscriptionOrders(
        \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
    ){
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('status', SubscriptionOrder::SUCCESS_STATUS, 'eq')
            ->addFilter('subscription_customer_id', $subscriptionCustomer->getId(), 'eq')
            ->create();
        $subscriptionOrderList = $this->subscriptionOrderRepository->getList($searchCriteria);
        return $subscriptionOrderList->getTotalCount();
    }

    /**
     * See if subscription has expired
     *
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @param \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface $subscriptionPlan
     * @return boolean
     *
     */
    protected function isSubscriptionExpired(
        \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer,
        \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface $subscriptionPlan
    ){
        if(!$subscriptionPlan->getDuration()){
            return false;
        }

        if($subscriptionCustomer->getEndDate()){
            $now = Carbon::now();
            $endDate = Carbon::createFromTimeString($subscriptionCustomer->getEndDate());
            if($endDate->lessThan($now)){
                return true;
            }
        }

        $subscriptionCustomerWasRecharged = false;
        if(!$subscriptionCustomerWasRecharged){
            $numSubscriptionOrders = $this->countSuccessFullSubscriptionOrders($subscriptionCustomer);
            $subscriptionHasOrdersLeft = $numSubscriptionOrders < $subscriptionPlan->getDuration();
            if($subscriptionHasOrdersLeft){
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * Handle an expired subscription customer
     *
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @return void
     *
     */
    protected function handleExpiredSubscriptionCustomer(
        \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
    ){
        $this->log("Subscription customer ID: " . $subscriptionCustomer->getId() . " has expired, setting to inactive");
        $subscriptionCustomer->setStatus(SubscriptionCustomer::AUTORENEW_OFF_STATUS);
    }

    /**
     * Check if subscription has free orders
     *
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @param \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface $subscriptionPlan
     * @return boolean
     *
     */
    protected function subscriptionHasFreeOrders(
        \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer,
        \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface $subscriptionPlan
    ){
        if(!$subscriptionPlan->getNumberOfFreeShipments() || $subscriptionPlan->getNumberOfFreeShipments() == 0){
            return false;
        }
        $numSuccessFullSubscriptionOrders = $this->countSuccessFullSubscriptionOrders($subscriptionCustomer);
        if($numSuccessFullSubscriptionOrders < $subscriptionPlan->getNumberOfFreeShipments()){
            return true;
        }
        return false;
    }

    /**
     * Sends notification to appropriate notifier class
     *
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @param int $attemptNumber
     * @return boolean
     *
     */
    protected function notifyCustomerOfError(
        $quote,
        \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer,
        int $attemptNumber
    ){
        $this->log("Customer Notification - Payment Declined - Attempt Number - " . $attemptNumber);
        $emailTemplateVariables = [
            "quote" => $quote,
            "subscriptionCustomer" => $subscriptionCustomer,
            "subscriptionPlan" => $subscriptionCustomer->getSubscriptionPlan(),
            "attemptNumber" => $attemptNumber,
            "maskedCardNumber" => $this->_last4 ? "XXXX-XXXX-XXXX-" . $this->_last4 : ""
        ];
        $this->emailHelper->sendAutoRenewChargeFailureEmail($quote->getCustomer(), $emailTemplateVariables);
    }

    protected function log($message)
    {
        return $this->logger->logToSubscriptionCron($message);
    }
}
