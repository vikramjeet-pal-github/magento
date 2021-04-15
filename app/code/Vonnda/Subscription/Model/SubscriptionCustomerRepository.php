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

use Vonnda\Subscription\Api\SubscriptionCustomerRepositoryInterface;
use Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface;

use Vonnda\Subscription\Model\SubscriptionCustomerSearchResultFactory;
use Vonnda\Subscription\Model\SubscriptionCustomerFactory;

use Vonnda\Subscription\Model\ResourceModel\SubscriptionCustomer\Collection;
use Vonnda\Subscription\Model\ResourceModel\SubscriptionCustomer\CollectionFactory as SubscriptionCustomerCollectionFactory;

use Vonnda\Subscription\Model\SubscriptionHistoryFactory;
use Vonnda\Subscription\Model\SubscriptionHistoryRepository;
use Vonnda\Subscription\Helper\SerializeHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Backend\Model\Auth\Session as AuthSession;

use Magento\Framework\App\State as AppState;
use Magento\Framework\App\Area;

class SubscriptionCustomerRepository implements SubscriptionCustomerRepositoryInterface
{
    /**
     * @var SubscriptionCustomerFactory
     */
    private $subscriptionCustomerFactory;

    /**
     * @var SubscriptionCustomerCollectionFactory
     */
    private $subscriptionCustomerCollectionFactory;

    /**
     * @var SubscriptionCustomerSearchResultFactory
     */
    private $searchResultFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var SubscriptionHistoryFactory
     */
    protected $subscriptionHistoryFactory;

    /**
     * @var SubscriptionHistoryRepository
     */
    protected $subscriptionHistoryRepository;

    /**
     * @var SerializeHelper
     */
    protected $serializeHelper;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var AuthSession
     */
    protected $authSession;

    /**
     * @var State
     */
    protected $appState;

    public function __construct(
        SubscriptionCustomerFactory $subscriptionCustomerFactory,
        SubscriptionCustomerCollectionFactory $subscriptionCustomerCollectionFactory,
        SubscriptionCustomerSearchResultFactory $subscriptionCustomerSearchResultFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SubscriptionHistoryFactory $subscriptionHistoryFactory,
        SubscriptionHistoryRepository $subscriptionHistoryRepository,
        SerializeHelper $serializeHelper,
        CustomerSession $customerSession,
        AuthSession $authSession,
        AppState $appState
    ) {
        $this->subscriptionCustomerFactory = $subscriptionCustomerFactory;
        $this->subscriptionCustomerCollectionFactory = $subscriptionCustomerCollectionFactory;
        $this->searchResultFactory = $subscriptionCustomerSearchResultFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->subscriptionHistoryFactory = $subscriptionHistoryFactory;
        $this->subscriptionHistoryRepository = $subscriptionHistoryRepository;
        $this->serializeHelper = $serializeHelper;
        $this->customerSession = $customerSession;
        $this->authSession = $authSession;
        $this->appState = $appState;
    }

    public function getById($id)
    {
        $subscriptionCustomer = $this->subscriptionCustomerFactory->create();
        $subscriptionCustomer->getResource()->load($subscriptionCustomer, $id);
        if (!$subscriptionCustomer->getId()) {
            throw new NoSuchEntityException(__('Unable to find subscriptionCustomer with ID "%1"', $id));
        }
        return $subscriptionCustomer;
    }

    public function save(SubscriptionCustomerInterface $subscriptionCustomer)
    {        
        $subscriptionCustomer->setUpdatedAt(null);
        $subscriptionHistory = $this->subscriptionHistoryFactory->create();
        $beforeSave = $this->subscriptionCustomerFactory->create();
        $beforeSave->getResource()->load($beforeSave, $subscriptionCustomer->getId());
        $serializedBeforeSave = $this->serializeHelper->serializeSubscriptionCustomer($beforeSave);
        $subscriptionHistory->setBeforeSave($serializedBeforeSave);
        $currentCustomer = $this->customerSession->getCustomer();
        $admin = $this->authSession->getUser();
        if($currentCustomer && $currentCustomer->getId()){
            $subscriptionHistory->setCustomerId($currentCustomer->getId());
        } else if($admin && $admin->getId()){
            $subscriptionHistory->setAdminUserId($admin->getId());
        } else if($this->appState->getAreaCode() === Area::AREA_WEBAPI_REST){
            $subscriptionHistory->setCustomerId($subscriptionCustomer->getCustomerId());
        }
        $subscriptionCustomer->getResource()->save($subscriptionCustomer);
        $afterSave = $this->subscriptionCustomerFactory->create();
        $afterSave->getResource()->load($afterSave, $subscriptionCustomer->getId());
        $serializedAfterSave = $this->serializeHelper->serializeSubscriptionCustomer($afterSave);
        $subscriptionHistory->setSubscriptionCustomerId($subscriptionCustomer->getId())
            ->setAfterSave($serializedAfterSave);
        $subscriptionHistory->save();
        return $subscriptionCustomer;
    }

    public function delete(SubscriptionCustomerInterface $subscriptionCustomer)
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
     * Returns all subscription customer by customer id
     *
     * @param int $subscriptionCustomerId
     * @return false|\Vonnda\Subscription\Model\SubscriptionPayment
     * 
     */
    public function getSubscriptionCustomersByCustomerId(int $customerId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('customer_id', $customerId,'eq')
            ->create();

        $subscriptionCustomerList = $this->getList($searchCriteria);
        return $subscriptionCustomerList;
    }

    /**
     * Returns first subscription customer by parent order id
     *
     * @param int $subscriptionCustomerId
     * @return false|\Vonnda\Subscription\Model\SubscriptionPayment
     * 
     */
    public function getFirstByParentOrderId($parentOrderId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('parent_order_id', $parentOrderId,'eq')
            ->create();

        $subscriptionCustomerList = $this->getList($searchCriteria);
        foreach($subscriptionCustomerList->getItems() as $item){
            return $item;
        }

        return null;
    }

    /**
     * Returns all subscription customers by parent order id
     *
     * @param int $subscriptionCustomerId
     * @return false|\Vonnda\Subscription\Model\SubscriptionPayment
     * 
     */
    public function getAllByParentOrderId($parentOrderId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('parent_order_id', $parentOrderId,'eq')
            ->create();

        $subscriptionCustomerList = $this->getList($searchCriteria);
        return $subscriptionCustomerList->getItems();
    }
}