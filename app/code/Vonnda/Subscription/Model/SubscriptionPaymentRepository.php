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

use Vonnda\Subscription\Api\SubscriptionPaymentRepositoryInterface;
use Vonnda\Subscription\Api\Data\SubscriptionPaymentInterface;

use Vonnda\Subscription\Model\SubscriptionPaymentSearchResultFactory;
use Vonnda\Subscription\Model\SubscriptionPaymentFactory;

use Vonnda\Subscription\Model\ResourceModel\SubscriptionPayment\Collection;
use Vonnda\Subscription\Model\ResourceModel\SubscriptionPayment\CollectionFactory as SubscriptionPaymentCollectionFactory;

class SubscriptionPaymentRepository implements SubscriptionPaymentRepositoryInterface
{
    /**
     * @var SubscriptionPaymentFactory
     */
    private $subscriptionCustomerFactory;

    /**
     * @var SubscriptionPaymentCollectionFactory
     */
    private $subscriptionCustomerCollectionFactory;

    /**
     * @var SubscriptionPaymentSearchResultFactory
     */
    private $searchResultFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    public function __construct(
        SubscriptionPaymentFactory $subscriptionCustomerFactory,
        SubscriptionPaymentCollectionFactory $subscriptionCustomerCollectionFactory,
        SubscriptionPaymentSearchResultFactory $subscriptionCustomerSearchResultFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->subscriptionCustomerFactory = $subscriptionCustomerFactory;
        $this->subscriptionCustomerCollectionFactory = $subscriptionCustomerCollectionFactory;
        $this->searchResultFactory = $subscriptionCustomerSearchResultFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function getById($id)
    {
        $subscriptionCustomer = $this->subscriptionCustomerFactory->create();
        $subscriptionCustomer->getResource()->load($subscriptionCustomer, $id);
        if (!$subscriptionCustomer->getId()) {
            throw new NoSuchEntityException(__('Unable to find subscriptionPayment with ID "%1"', $id));
        }
        return $subscriptionCustomer;
    }

    public function save(SubscriptionPaymentInterface $subscriptionCustomer)
    {
        $subscriptionCustomer->getResource()->save($subscriptionCustomer);
        return $subscriptionCustomer;
    }

    public function delete(SubscriptionPaymentInterface $subscriptionCustomer)
    {
        $subscriptionCustomer->getResource()->delete($subscriptionCustomer);
    }

    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->subscriptionCustomerCollectionFactory->create();

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
     * Return subscription payment by subscription customer id
     *
     * @param int $subscriptionCustomerId
     * @return false|\Vonnda\Subscription\Model\SubscriptionPayment
     * 
     */
    public function getSubscriptionPaymentBySubscriptionCustomerId(int $subscriptionCustomerId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('subscription_customer_id', $subscriptionCustomerId,'eq')
            ->create();

        $subscriptionPaymentList = $this->getList($searchCriteria);
        foreach($subscriptionPaymentList->getItems() as $item){
            return $item;
        }

        return false;
    }
}