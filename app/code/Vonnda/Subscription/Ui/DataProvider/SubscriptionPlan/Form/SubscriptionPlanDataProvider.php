<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Ui\DataProvider\SubscriptionPlan\Form;

use Vonnda\Subscription\Model\ResourceModel\SubscriptionPlan\CollectionFactory;
use Vonnda\Subscription\Model\SubscriptionPlan;
use Vonnda\Subscription\Model\SubscriptionProductRepository;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\Api\SearchCriteriaBuilder;

class SubscriptionPlanDataProvider extends AbstractDataProvider
{

    protected $subscriptionProductRepository;

    protected $searchCriteriaBuilder;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $subscriptionPlanCollectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $subscriptionPlanCollectionFactory,
        array $meta = [],
        array $data = [],
        SubscriptionProductRepository $subscriptionProductRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->subscriptionProductRepository = $subscriptionProductRepository;
        $this->collection = $subscriptionPlanCollectionFactory->create();
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        $this->loadedData = array();
        /** @var SubscriptionPlan $subscriptionPlan */
        foreach ($items as $subscriptionPlan) {
            $this->loadedData[$subscriptionPlan->getId()]['subscriptionPlan'] = $subscriptionPlan->getData();
            $this->loadedData[$subscriptionPlan->getId()]['subscriptionPlan']['subscription_products'] = $this->getSubscriptionProductsJSON($subscriptionPlan->getId());
        }

        return $this->loadedData;

    }

    public function getSubscriptionProductsJSON($subscriptionPlanId)
    {
        try {
            $searchCriteria = $this->searchCriteriaBuilder
                                    ->addFilter('subscription_plan_id',$subscriptionPlanId,'eq')
                                    ->create();
            $subscriptionProductList = $this->subscriptionProductRepository->getList($searchCriteria);
            
            $returnArr = [];
            foreach($subscriptionProductList->getItems() as $product){
                $returnArr[] = ["id" => $product->getProductId(), "qty" => $product->getQty()];
            }

            return json_encode($returnArr);
        } catch(\Exception $e){
            return false;
        }
    }
}