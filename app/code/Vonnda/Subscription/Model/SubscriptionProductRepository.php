<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\NoSuchEntityException;

use Vonnda\Subscription\Api\SubscriptionProductRepositoryInterface;
use Vonnda\Subscription\Api\Data\SubscriptionProductInterface;

use Vonnda\Subscription\Model\SubscriptionProductSearchResultFactory;
use Vonnda\Subscription\Model\SubscriptionProductFactory;

use Vonnda\Subscription\Model\ResourceModel\SubscriptionProduct\Collection;
use Vonnda\Subscription\Model\ResourceModel\SubscriptionProduct\CollectionFactory as SubscriptionProductCollectionFactory;

class SubscriptionProductRepository implements SubscriptionProductRepositoryInterface
{
    /**
     * @var SubscriptionProductFactory
     */
    private $subscriptionProductFactory;

    /**
     * @var SubscriptionProductCollectionFactory
     */
    private $subscriptionProductCollectionFactory;

    /**
     * @var SubscriptionProductSearchResultFactory
     */
    private $searchResultFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    public function __construct(
        SubscriptionProductFactory $subscriptionProductFactory,
        SubscriptionProductCollectionFactory $subscriptionProductCollectionFactory,
        SubscriptionProductSearchResultFactory $subscriptionProductSearchResultFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->subscriptionProductFactory = $subscriptionProductFactory;
        $this->subscriptionProductCollectionFactory = $subscriptionProductCollectionFactory;
        $this->searchResultFactory = $subscriptionProductSearchResultFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function getById($id)
    {
        $subscriptionProduct = $this->subscriptionProductFactory->create();
        $subscriptionProduct->getResource()->load($subscriptionProduct, $id);
        if (!$subscriptionProduct->getId()) {
            throw new NoSuchEntityException(__('Unable to find subscriptionProduct with ID "%1"', $id));
        }
        return $subscriptionProduct;
    }

    public function save(SubscriptionProductInterface $subscriptionProduct)
    {
        $subscriptionProduct->getResource()->save($subscriptionProduct);
        return $subscriptionProduct;
    }

    public function delete(SubscriptionProductInterface $subscriptionProduct)
    {
        $subscriptionProduct->getResource()->delete($subscriptionProduct);
    }

    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->subscriptionProductCollectionFactory->create();

        $this->addFiltersToCollection($searchCriteria, $collection);
        $this->addSortOrdersToCollection($searchCriteria, $collection);
        $this->addPagingToCollection($searchCriteria, $collection);

        $collection->load();

        return $this->buildSearchResult($searchCriteria, $collection);
    }

    private function addFiltersToCollection(SearchCriteriaInterface $searchCriteria, Collection $collection)
    {
        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            $fields = $conditions = [];
            foreach ($filterGroup->getFilters() as $filter) {
                $fields[] = $filter->getField();
                $conditions[] = [$filter->getConditionType() => $filter->getValue()];
            }
            $collection->addFieldToFilter($fields, $conditions);
        }
    }

    private function addSortOrdersToCollection(SearchCriteriaInterface $searchCriteria, Collection $collection)
    {
        foreach ((array)$searchCriteria->getSortOrders() as $sortOrder) {
            $direction = $sortOrder->getDirection() == SortOrder::SORT_ASC ? 'asc' : 'desc';
            $collection->addOrder($sortOrder->getField(), $direction);
        }
    }

    private function addPagingToCollection(SearchCriteriaInterface $searchCriteria, Collection $collection)
    {
        $collection->setPageSize($searchCriteria->getPageSize());
        $collection->setCurPage($searchCriteria->getCurrentPage());
    }

    private function buildSearchResult(SearchCriteriaInterface $searchCriteria, Collection $collection)
    {
        $searchResults = $this->searchResultFactory->create();

        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * Get a collection of subscription products according to subscription_plan_id
     *
     * @param int $subscriptionPlanId
     * @return \Vonnda\Subscription\Model\ResourceModel\SubscriptionProduct\Collection
     * 
     */
    public function getSubscriptionProductsByPlanId(int $subscriptionPlanId)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('subscription_plan_id',$subscriptionPlanId,'eq')->create();
        return $this->getList($searchCriteria);
    }
}