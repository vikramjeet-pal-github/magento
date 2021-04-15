<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Block\Adminhtml\SubscriptionPlan;

use Vonnda\Subscription\Model\SubscriptionPlanRepository;
use Vonnda\Subscription\Helper\Logger;

use Carbon\Carbon;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Backend\Model\UrlInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;


class PromoChooser extends Template
{
    /**
     * Subscription Plan Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionPlanRepository $subscriptionPlanRepository
     */
    protected $subscriptionPlanRepository;

    /**
     * Request Object
     *
     * @var \Magento\Framework\App\RequestInterface $request
     */
    protected $request;


    /**
     * Backend Url
     *
     * @var \Magento\Backend\Model\UrlInterface $backendUrlInterface
     */
    protected $backendUrlInterface;


    /**
     * Sales Rule Repository
     *
     * @var \Magento\SalesRule\Api\RuleRepositoryInterface $salesRuleRepository
     */
    protected $salesRuleRepository;

    /**
     * Search Criteria Builder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    protected $logger;
    
    /**
     * 
     * Subscription Plan PromoChooser Block
     * 
     * @param Context $context
     * @param RequestInterface $request
     * @param SubscriptionPlanRepository $subscriptionPlanRepository
     * @param UrlInterface $backendUrlInterface
     * @param RuleRepositoryInterface $salesRuleRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * 
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        SubscriptionPlanRepository $subscriptionPlanRepository,
        UrlInterface $backendUrlInterface,
        RuleRepositoryInterface $salesRuleRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Logger $logger
    ){
        $this->request = $request;
        $this->subscriptionPlanRepository = $subscriptionPlanRepository;
        $this->backendUrlInterface = $backendUrlInterface;
        $this->salesRuleRepository = $salesRuleRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
        parent::__construct($context);
	}

    public function getSubscriptionPlan()
    {
        $subscriptionPlanId = $this->request->getParam('id');
        if($subscriptionPlanId){
            try {
                return $this->subscriptionPlanRepository->getById($subscriptionPlanId);
            } catch(\Exception $e){
                return null;
            }
        } else {
            return null;
        }
    }

    public function getAvailablePromoCodes()
    {
        $searchCriteria = $this->searchCriteriaBuilder
                               ->addFilter('is_active', true,'eq')
                               ->addFilter('use_auto_generation', true,'eq')
                               ->create();
        $salesRuleList = $this->salesRuleRepository->getList($searchCriteria);
        $returnArr = [];
        foreach($salesRuleList->getItems() as $salesRule){
            $returnArr[] = [
                'id' => $salesRule->getRuleId(),
                'name' => $salesRule->getName(),
                "description" => $salesRule->getDescription()
            ];
        }

        return $returnArr;
    }

    public function getSubscriptionPlanPromosJSON()
    {
        $subscriptionPlan = $this->getSubscriptionPlan();
        $returnArr = [];
        if($subscriptionPlan && $subscriptionPlan->getDefaultPromoIds()){
            $ruleIdsArr = explode(',',$subscriptionPlan->getDefaultPromoIds());
            foreach($ruleIdsArr as $ruleId){
                $rule = $this->salesRuleRepository->getById($ruleId);
                $returnArr[] = [
                            "id" => $rule->getRuleId(),
                            "name" => $rule->getName(),
                            "description" => $rule->getDescription()
                ];
            }
        }
        return json_encode($returnArr);
    }
}