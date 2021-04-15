<?php

namespace Vonnda\Subscription\Model\Source\SubscriptionPlan;

use Magento\Framework\Option\ArrayInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Store\Model\StoreManagerInterface;

class StoreIdSelect implements ArrayInterface
{
    protected $searchCriteriaBuilder;

    protected $storeRepository;

    protected $storeManager;
    
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StoreRepositoryInterface $storeRepository,
        StoreManagerInterface $storeManager
    ){
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeRepository = $storeRepository;
        $this->storeManager = $storeManager;
    }
    
    
    public function toOptionArray()
    {
        $stores = $this->storeManager->getStores();
        $optionArray = [];
        foreach($stores as $store){
            if($store->getCode() === 'Admin'){
                continue;
            }

            $optionArray[] = [
                'value' => $store->getId(), 'label' => __($store->getName())
            ];
        }

        return $optionArray;
    }
}