<?php

namespace Vonnda\Subscription\Model\Source\SubscriptionCustomer;

use Vonnda\Subscription\Model\SubscriptionPlanRepository;

use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class SubscriptionPlanSelect implements ArrayInterface
{
    protected $subscriptionPlanRepository;

    protected $searchCriteriaBuilder;

    public function __construct(
        SubscriptionPlanRepository $subscriptionPlanRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ){
        $this->subscriptionPlanRepository = $subscriptionPlanRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function toOptionArray()
    {
        $dataArray = [];

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('status','active','eq')
            ->create();

        $subscriptionPlans = $this->subscriptionPlanRepository->getList($searchCriteria);

        foreach($subscriptionPlans->getItems() as $item){
            $dataArray[] = ['value' => $item->getId(), 'label' => __($item->getTitle())];
        }

        return $dataArray;
    }
}