<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Model;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\NoSuchEntityException;

use Vonnda\Subscription\Api\SubscriptionOrderRepositoryInterface;
use Vonnda\Subscription\Api\Data\SubscriptionOrderInterface;

use Vonnda\Subscription\Model\SubscriptionOrderSearchResultFactory;
use Vonnda\Subscription\Model\SubscriptionOrderFactory;

use Vonnda\Subscription\Model\ResourceModel\SubscriptionOrder\Collection;
use Vonnda\Subscription\Model\ResourceModel\SubscriptionOrder\CollectionFactory as SubscriptionOrderCollectionFactory;

class SubscriptionOrderRepository implements SubscriptionOrderRepositoryInterface
{
    /**
     * @var SubscriptionOrderFactory
     */
    private $subscriptionOrderFactory;

    /**
     * @var SubscriptionOrderCollectionFactory
     */
    private $subscriptionOrderCollectionFactory;

    /**
     * @var SubscriptionOrderSearchResultFactory
     */
    private $searchResultFactory;

    public function __construct(
        SubscriptionOrderFactory $subscriptionOrderFactory,
        SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory,
        SubscriptionOrderSearchResultFactory $subscriptionOrderSearchResultFactory
    ) {
        $this->subscriptionOrderFactory = $subscriptionOrderFactory;
        $this->subscriptionOrderCollectionFactory = $subscriptionOrderCollectionFactory;
        $this->searchResultFactory = $subscriptionOrderSearchResultFactory;
    }

    public function getById($id)
    {
        $subscriptionOrder = $this->subscriptionOrderFactory->create();
        $subscriptionOrder->getResource()->load($subscriptionOrder, $id);
        if (!$subscriptionOrder->getId()) {
            throw new NoSuchEntityException(__('Unable to find subscriptionOrder with ID "%1"', $id));
        }
        return $subscriptionOrder;
    }

    public function save(SubscriptionOrderInterface $subscriptionOrder)
    {
        $subscriptionOrder->getResource()->save($subscriptionOrder);
        return $subscriptionOrder;
    }

    public function delete(SubscriptionOrderInterface $subscriptionOrder)
    {
        $subscriptionOrder->getResource()->delete($subscriptionOrder);
    }

    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->subscriptionOrderCollectionFactory->create();

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