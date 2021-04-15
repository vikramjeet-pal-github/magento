<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Cron;

use Vonnda\Subscription\Model\SubscriptionCustomer;
use Vonnda\Subscription\Model\SubscriptionCustomerRepository;
use Vonnda\Subscription\Model\SubscriptionPaymentRepository;
use Vonnda\Subscription\Model\SubscriptionPlanRepository;
use Vonnda\Subscription\Model\SubscriptionProductRepository;
use Vonnda\Subscription\Model\SubscriptionPromoRepository;
use Vonnda\Subscription\Model\SubscriptionOrderRepository;
use Vonnda\Subscription\Helper\TimeDateHelper;
use Vonnda\Subscription\Helper\Data as SubscriptionHelper;
use Vonnda\Subscription\Helper\Logger as LoggerInterface;
use Vonnda\Subscription\Helper\EmailHelper;

use Carbon\Carbon;

use Magento\Framework\App\State;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Model\ProductRepository;


/**
 * OnCheckoutSuccess Observer
 */
class SendUpdateEmails
{
    /**
     * Subscription Customer Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionCustomerRepository $subscriptionCustomerRepository
     */
    protected $subscriptionCustomerRepository;

    /**
     * App State
     *
     * @var \Magento\Framework\App\State $state
     */
    protected $state;

    /**
     * Store Manager Interface
     *
     * @var \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * Customer Repository Interface
     *
     * @var \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
     */
    protected $customerRepositoryInterface;

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
     * Email Helper
     *
     * @var \Vonnda\Subscription\Helper\EmailHelper $emailHelper
     */
    protected $emailHelper;

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
     * 
     * SendUpdateEmails Constructor
     * 
     * @param SubscriptionCustomerRepository $subscriptionCustomerRepository
     * @param State $state
     * @param StoreManagerInterface $storeManagerInterface
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param LoggerInterface $logger
     * @param ProductRepository $productRepository
     * @param SubscriptionPlanRepository $subscriptionPlanRepository
     * @param SubscriptionProductRepository $subscriptionProductRepository
     * @param SubscriptionHelper $subscriptionHelper
     * @param TimeDateHelper $timeDateHelper
     * @param emailHelper $emailHelper
     * @param SubscriptionPaymentRepository $subscriptionPaymentRepository
     * @param SubscriptionPromoRepository $subscriptionPromoRepository
     * @param SubscriptionOrderRepository $subscriptionOrderRepository
     */
    public function __construct(
        SubscriptionCustomerRepository $subscriptionCustomerRepository,
        State $state,
        StoreManagerInterface $storeManagerInterface,
        CustomerRepositoryInterface $customerRepositoryInterface,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger,
        ProductRepository $productRepository,
        SubscriptionPlanRepository $subscriptionPlanRepository,
        SubscriptionProductRepository $subscriptionProductRepository,
        TimeDateHelper $timeDateHelper,
        EmailHelper $emailHelper,
        SubscriptionHelper $subscriptionHelper,
        SubscriptionPaymentRepository $subscriptionPaymentRepository,
        SubscriptionPromoRepository $subscriptionPromoRepository,
        SubscriptionOrderRepository $subscriptionOrderRepository
    ){
        $this->state = $state;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->subscriptionHelper = $subscriptionHelper;
        $this->timeDateHelper = $timeDateHelper;
        $this->emailHelper = $emailHelper;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->subscriptionProductRepository = $subscriptionProductRepository;
        $this->subscriptionPlanRepository = $subscriptionPlanRepository;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
        $this->subscriptionPromoRepository = $subscriptionPromoRepository;
        $this->subscriptionOrderRepository = $subscriptionOrderRepository;
    }
 
    /**
     * Iterate through subscription customers and send e-mails
     *
     * @return $this
     * @throws \Exception
     */
    public function execute() {
        $this->logger->info("Email updates cron started");

        $this->sendThirtyDayRenewalEmails($this->getOrdersInDays(30));
        
        $this->sendTenDayShippingConfirmationEmails($this->getOrdersInDays(10));

        $this->logger->info("Email updates cron finished");
    }

    /**
     * Get all of todays active orders with a renewal date in a specified number of days
     *
     * @param int $days
     * @return \Vonnda\Subscription\Model\ResourceModel\SubscriptionCustomer\Collection
     * 
     */
    public function getOrdersInDays(int $days)
    {
        $from = Carbon::now()->addDays($days)->setTimezone('America/Los_Angeles')->startOfDay()->toDateTimeString();
        $to = Carbon::now()->addDays($days + 1)->setTimezone('America/Los_Angeles')->startOfDay()->toDateTimeString();
        $this->logger->info("Sending update e-mails for from " . $from . " to " . $to);

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('state', SubscriptionCustomer::ACTIVE_STATE ,'eq')
            ->addFilter('next_order',$from,'gteq')
            ->addFilter('next_order',$to,'lteq')
            ->create();

        return $this->subscriptionCustomerRepository->getList($searchCriteria);
    }

    public function sendThirtyDayRenewalEmails($subscriptionCustomerList)
    {
        if($subscriptionCustomerList->getTotalCount() > 0){
            foreach($subscriptionCustomerList->getItems() as $subscriptionCustomer){
                try {
                    $customer = $this->customerRepositoryInterface->getById($subscriptionCustomer->getCustomerId());
                    $emailTemplateVariables = [
                        "subscriptionCustomer" => $subscriptionCustomer,
                        "days" => 30
                    ];
                    $message = [
                        'customer_email' => $customer->getEmail(),
                        'customer_id' => $customer->getId(),
                        'template_vars' => $emailTemplateVariables
                    ];
                    $this->logger->logToThirtyDayEmailDebugLog(json_encode($message));
                    $this->emailHelper->send30DayUpcomingRefillShipDateEmail($customer, $emailTemplateVariables);
                } catch(\Exception $e){
                    $this->logger->info('Error sending renewal reminder e-mail');
                    $this->logger->info($e->getMessage());
                }
            }
        } else {
            $this->logger->info("No 30-day renewal e-mails to be sent today");
        }
        return $this;
    }

    public function sendTenDayShippingConfirmationEmails($subscriptionCustomerList)
    {
        if($subscriptionCustomerList->getTotalCount() > 0){
            foreach($subscriptionCustomerList->getItems() as $subscriptionCustomer){
                try {
                    $customer = $this->customerRepositoryInterface->getById($subscriptionCustomer->getCustomerId());
                    $emailTemplateVariables = [
                        "subscriptionCustomer" => $subscriptionCustomer,
                        "days" => 10
                    ];
                    $message = [
                        'customer_email' => $customer->getEmail(),
                        'customer_id' => $customer->getId(),
                        'template_vars' => $emailTemplateVariables
                    ];
                    $this->logger->logToTenDayEmailDebugLog(json_encode($message));
                    $this->emailHelper->send10DayUpcomingRefillShipDateEmail($customer, $emailTemplateVariables);
                } catch(\Exception $e){
                    $this->logger->info('Error sending shipping confirmation e-mail');
                    $this->logger->info($e->getMessage());
                }
            }
        } else {
            $this->logger->info("No 10-day address confirmation e-mails to be sent today");
        }
        return $this;
    }

}