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

use Vonnda\Subscription\Api\SubscriptionHistoryRepositoryInterface;
use Vonnda\Subscription\Api\Data\SubscriptionHistoryInterface;

use Vonnda\Subscription\Model\SubscriptionHistorySearchResultFactory;
use Vonnda\Subscription\Model\SubscriptionHistoryFactory;

use Vonnda\Subscription\Model\ResourceModel\SubscriptionHistory\Collection;
use Vonnda\Subscription\Model\ResourceModel\SubscriptionHistory\CollectionFactory as SubscriptionHistoryCollectionFactory;

class SubscriptionHistoryRepository implements SubscriptionHistoryRepositoryInterface
{
    /**
     * @var SubscriptionHistoryFactory
     */
    private $subscriptionHistoryFactory;

    /**
     * @var SubscriptionHistoryCollectionFactory
     */
    private $subscriptionHistoryCollectionFactory;

    /**
     * @var SubscriptionHistorySearchResultFactory
     */
    private $searchResultFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    public function __construct(
        SubscriptionHistoryFactory $subscriptionHistoryFactory,
        SubscriptionHistoryCollectionFactory $subscriptionHistoryCollectionFactory,
        SubscriptionHistorySearchResultFactory $subscriptionHistorySearchResultFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->subscriptionHistoryFactory = $subscriptionHistoryFactory;
        $this->subscriptionHistoryCollectionFactory = $subscriptionHistoryCollectionFactory;
        $this->searchResultFactory = $subscriptionHistorySearchResultFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function getById($id)
    {
        $subscriptionHistory = $this->subscriptionHistoryFactory->create();
        $subscriptionHistory->getResource()->load($subscriptionHistory, $id);
        if (!$subscriptionHistory->getId()) {
            throw new NoSuchEntityException(__('Unable to find subscriptionHistory with ID "%1"', $id));
        }
        return $subscriptionHistory;
    }

    public function save(SubscriptionHistoryInterface $subscriptionHistory)
    {
        $subscriptionHistory->getResource()->save($subscriptionHistory);
        return $subscriptionHistory;
    }

    public function delete(SubscriptionHistoryInterface $subscriptionHistory)
    {
        $subscriptionHistory->getResource()->delete($subscriptionHistory);
    }

    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->subscriptionHistoryCollectionFactory->create();

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
}