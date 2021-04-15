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

use Vonnda\Subscription\Api\SubscriptionPlanRepositoryInterface;
use Vonnda\Subscription\Api\Data\SubscriptionPlanInterface;

use Vonnda\Subscription\Model\SubscriptionPlanSearchResultFactory;
use Vonnda\Subscription\Model\SubscriptionPlanFactory;

use Vonnda\Subscription\Model\ResourceModel\SubscriptionPlan\Collection;
use Vonnda\Subscription\Model\ResourceModel\SubscriptionPlan\CollectionFactory as SubscriptionPlanCollectionFactory;

class SubscriptionPlanRepository implements SubscriptionPlanRepositoryInterface
{
    /**
     * @var SubscriptionPlanFactory
     */
    private $subscriptionPlanFactory;

    /**
     * @var SubscriptionPlanCollectionFactory
     */
    private $subscriptionPlanCollectionFactory;

    /**
     * @var SubscriptionPlanSearchResultFactory
     */
    private $searchResultFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    public function __construct(
        SubscriptionPlanFactory $subscriptionPlanFactory,
        SubscriptionPlanCollectionFactory $subscriptionPlanCollectionFactory,
        SubscriptionPlanSearchResultFactory $subscriptionPlanSearchResultFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->subscriptionPlanFactory = $subscriptionPlanFactory;
        $this->subscriptionPlanCollectionFactory = $subscriptionPlanCollectionFactory;
        $this->searchResultFactory = $subscriptionPlanSearchResultFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function getById($id)
    {
        $subscriptionPlan = $this->subscriptionPlanFactory->create();
        $subscriptionPlan->getResource()->load($subscriptionPlan, $id);
        if (!$subscriptionPlan->getId()) {
            throw new NoSuchEntityException(__('Unable to find subscriptionPlan with ID "%1"', $id));
        }
        return $subscriptionPlan;
    }

    public function getByIdentifier($identifier)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('identifier', $identifier, 'eq')
            ->create();
        $subscriptionPlans = $this->getList($searchCriteria)->getItems();
        foreach($subscriptionPlans as $subscriptionPlan){
            return $subscriptionPlan;
        }
        return null;
    }

    public function save(SubscriptionPlanInterface $subscriptionPlan)
    {
        $subscriptionPlan->getResource()->save($subscriptionPlan);
        return $subscriptionPlan;
    }

    public function delete(SubscriptionPlanInterface $subscriptionPlan)
    {
        $subscriptionPlan->getResource()->delete($subscriptionPlan);
    }

    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->subscriptionPlanCollectionFactory->create();

        $this->addFiltersToCollection($searchCriteria, $collection);
        $this->addSortOrdersToCollection($searchCriteria, $collection);
        $this->addPagingToCollection($searchCriteria, $collection);

        $collection->load();

        return $this->buildSearchResult($searchCriteria, $collection);
    }

    //This returns first
    public function getFirstByIdentifier($identifier)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('identifier', $identifier,'eq')
            ->setPageSize(1)
            ->create();

        $subscriptionPaymentList = $this->getList($searchCriteria);
        foreach($subscriptionPaymentList->getItems() as $item){
            return $item;
        }

        return null;
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
}