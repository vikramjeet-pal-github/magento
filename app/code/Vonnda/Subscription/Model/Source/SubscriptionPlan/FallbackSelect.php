<?php

namespace Vonnda\Subscription\Model\Source\SubscriptionPlan;

use Vonnda\Subscription\Api\SubscriptionPlanRepositoryInterface;

use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Store\Model\StoreManagerInterface;

class FallbackSelect implements ArrayInterface
{
    protected $subscriptionPlanRepository;
    
    protected $searchCriteriaBuilder;

    protected $storeManager;
    
    public function __construct(
        SubscriptionPlanRepositoryInterface $subscriptionPlanRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StoreManagerInterface $storeManager
    ){
        $this->subscriptionPlanRepository = $subscriptionPlanRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeManager = $storeManager;
    }
    
    public function toOptionArray()
    {
        $dataArray = [];
        $dataArray[] = ['value' => "", 'label' => __("None")];

        $searchCriteria = $this->searchCriteriaBuilder->create();
        $subscriptionPlans = $this->subscriptionPlanRepository->getList($searchCriteria);

        foreach($subscriptionPlans->getItems() as $item){
            $dataArray[] = ['value' => $item->getIdentifier(), 'label' => __($item->getIdentifier())];
        }

        return $dataArray;
    }
}