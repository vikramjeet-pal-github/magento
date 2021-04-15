<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Cron;

use Vonnda\Subscription\Model\SubscriptionCustomer;
use Vonnda\Subscription\Model\SubscriptionCustomerRepository;
use Vonnda\Subscription\Model\SubscriptionPlanRepository;
use Vonnda\Subscription\Helper\TimeDateHelper;
use Vonnda\Subscription\Helper\Data as SubscriptionHelper;
use Vonnda\Subscription\Helper\Logger as LoggerInterface;

use Carbon\Carbon;

use Magento\Framework\Api\SearchCriteriaBuilder;

class updateExpiredSubscriptions
{
    const LOG_DEBUG = false;
    
    /**
     * Subscription Customer Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionCustomerRepository $subscriptionCustomerRepository
     */
    protected $subscriptionCustomerRepository;

    /**
     * Subscription Plan Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionPlanRepository $subscriptionPlanRepository
     */
    protected $subscriptionPlanRepository;

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
     * 
     * @param SubscriptionCustomerRepository $subscriptionCustomerRepository
     * @param SubscriptionPlanRepository $subscriptionPlanRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param LoggerInterface $logger
     * @param SubscriptionHelper $subscriptionHelper
     * @param TimeDateHelper $timeDateHelper
     */
    public function __construct(
        SubscriptionCustomerRepository $subscriptionCustomerRepository,
        SubscriptionPlanRepository $subscriptionPlanRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger,
        SubscriptionHelper $subscriptionHelper,
        TimeDateHelper $timeDateHelper
    ){
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->subscriptionPlanRepository = $subscriptionPlanRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
        $this->timeDateHelper = $timeDateHelper;
        $this->subscriptionHelper = $subscriptionHelper;
    }
 
    /**
     *
     * @return $this
     * @throws \Exception
     */
    public function execute() 
    {
        $this->updateFreeSubscriptions();
        $this->updateExpiredSubscriptions();
    }

    protected function getExpiredSubscriptions()
    {
        $to = Carbon::createMidnightDate()->subDays(1)->toDateTimeString();
        $this->logger->info("Processing expired subscriptions before " . $to);
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('state', SubscriptionCustomer::INACTIVE_STATE ,'neq')
            ->addFilter('end_date', $to, 'lteq')
            ->create();

        return $this->subscriptionCustomerRepository->getList($searchCriteria)->getItems();
    }

    protected function updateExpiredSubscriptions()
    {
        $expiredSubscription = $this->getExpiredSubscriptions();
        foreach($expiredSubscription as $_subscription){
            $_subscription->setStatus(SubscriptionCustomer::AUTORENEW_COMPLETE_STATUS);
            $this->subscriptionCustomerRepository->save($_subscription);
        }
    }

    protected function updateFreeSubscriptions()
    {
        $to = Carbon::createMidnightDate()->subDays(1)->toDateTimeString();
        $this->logger->info("Processing expired free subscriptions before " . $to);
        
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('payment_required_for_free', 1, 'eq')
            ->create();
        $subscriptionPlans = $this->subscriptionPlanRepository->getList($searchCriteria)->getItems();
        foreach($subscriptionPlans as $subscriptionPlan){
            $expiredFreeSubscriptions = $this->getExpiredFreeSubscriptions($to, $subscriptionPlan->getId());
            $this->updateExpiredFreeSubscriptions($expiredFreeSubscriptions, $subscriptionPlan);
        }
    }

    protected function getExpiredFreeSubscriptions($to, $subscriptionPlanId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('state', SubscriptionCustomer::INACTIVE_STATE ,'eq')
            ->addFilter('subscription_plan_id', $subscriptionPlanId,'eq')
            ->addFilter('next_order', $to, 'lteq')
            ->create();
        $subscriptions = $this->subscriptionCustomerRepository->getList($searchCriteria)->getItems();
        $this->log(count($subscriptions) . " free subscriptions to be transitioned");
        return $subscriptions;
    }

    protected function updateExpiredFreeSubscriptions($subscriptionCustomers, $subscriptionPlan)
    {
        $count = count($subscriptionCustomers);
        foreach($subscriptionCustomers as $subscriptionCustomer){
            $this->logDebug("Updating subscription ID:" . $subscriptionCustomer->getId());
            if($subscriptionPlan->getFallbackPlan()){
                $newSubscriptionPlan = $this->subscriptionPlanRepository->getByIdentifier($subscriptionPlan->getFallbackPlan());
                if($newSubscriptionPlan){
                    $this->logDebug("Setting subscription to fallback plan " . $subscriptionPlan->getFallbackPlan());
                    $subscriptionCustomer->setSubscriptionPlanId($newSubscriptionPlan->getId())
                        ->setStatus(SubscriptionCustomer::NEW_NO_PAYMENT_STATUS);
                } else {
                    $this->log("Subscription plan with identifier '" . $subscriptionPlan->getFallbackPlan() . "' not found");
                }
            }
            $this->subscriptionCustomerRepository->save($subscriptionCustomer);
        }
        $this->log($count . " free subscriptions transitioned");
    }

    //TODO - use cronLogger
    protected function log($message){
        return $this->logger->info($message);
    }

    protected function logDebug($message){
        if(self::LOG_DEBUG){
            return $this->logger->info($message);
        }
        return;
    }

}