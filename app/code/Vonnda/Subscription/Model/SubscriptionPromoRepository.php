<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Model;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\NoSuchEntityException;

use Vonnda\Subscription\Api\SubscriptionPromoRepositoryInterface;
use Vonnda\Subscription\Api\Data\SubscriptionPromoInterface;

use Vonnda\Subscription\Model\SubscriptionPromoSearchResultFactory;
use Vonnda\Subscription\Model\SubscriptionPromoFactory;
use Vonnda\Subscription\Model\SubscriptionCustomerFactory;

use Vonnda\Subscription\Model\ResourceModel\SubscriptionPromo\Collection;
use Vonnda\Subscription\Model\ResourceModel\SubscriptionPromo\CollectionFactory as SubscriptionPromoCollectionFactory;

use Vonnda\Subscription\Model\SubscriptionHistoryFactory;
use Vonnda\Subscription\Model\SubscriptionHistoryRepository;
use Vonnda\Subscription\Helper\SerializeHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Backend\Model\Auth\Session as AuthSession;

class SubscriptionPromoRepository implements SubscriptionPromoRepositoryInterface
{
    /**
     * @var SubscriptionPromoFactory
     */
    protected $subscriptionPromoFactory;

     /**
     * @var SubscriptionCustomerFactory
     */
    protected $subscriptionCustomerFactory;

    /**
     * @var SubscriptionPromoCollectionFactory
     */
    protected $subscriptionPromoCollectionFactory;

    /**
     * @var SubscriptionPromoSearchResultFactory
     */
    protected $searchResultFactory;

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

    public function __construct(
        SubscriptionPromoFactory $subscriptionPromoFactory,
        SubscriptionPromoCollectionFactory $subscriptionPromoCollectionFactory,
        SubscriptionPromoSearchResultFactory $subscriptionPromoSearchResultFactory,
        SubscriptionCustomerFactory $subscriptionCustomerFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SubscriptionHistoryFactory $subscriptionHistoryFactory,
        SubscriptionHistoryRepository $subscriptionHistoryRepository,
        SerializeHelper $serializeHelper,
        CustomerSession $customerSession,
        AuthSession $authSession
    ) {
        $this->subscriptionPromoFactory = $subscriptionPromoFactory;
        $this->subscriptionPromoCollectionFactory = $subscriptionPromoCollectionFactory;
        $this->searchResultFactory = $subscriptionPromoSearchResultFactory;
        $this->subscriptionCustomerFactory = $subscriptionCustomerFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->subscriptionHistoryFactory = $subscriptionHistoryFactory;
        $this->subscriptionHistoryRepository = $subscriptionHistoryRepository;
        $this->serializeHelper = $serializeHelper;
        $this->customerSession = $customerSession;
        $this->authSession = $authSession;
    }

    public function getById($id)
    {
        $subscriptionPromo = $this->subscriptionPromoFactory->create();
        $subscriptionPromo->getResource()->load($subscriptionPromo, $id);
        if (!$subscriptionPromo->getId()) {
            throw new NoSuchEntityException(__('Unable to find subscriptionPromo with ID "%1"', $id));
        }
        return $subscriptionPromo;
    }

    public function save(SubscriptionPromoInterface $subscriptionPromo)
    {
        $subscriptionHistory = $this->subscriptionHistoryFactory->create();
        $beforeSave = $this->subscriptionCustomerFactory->create();
        $beforeSave->getResource()->load($beforeSave, $subscriptionPromo->getSubscriptionCustomerId());
        $serializedBeforeSave = $this->serializeHelper->serializeSubscriptionCustomer($beforeSave);
        $subscriptionHistory
            ->setSubscriptionCustomerId($subscriptionPromo->getSubscriptionCustomerId())
            ->setBeforeSave($serializedBeforeSave);
        $currentCustomer = $this->customerSession->getCustomer();
        $admin = $this->authSession->getUser();
        if($currentCustomer && $currentCustomer->getId()){
            $subscriptionHistory->setCustomerId($currentCustomer->getId());
        } else if($admin && $admin->getId()){
            $subscriptionHistory->setAdminUserId($admin->getId());
        }
        $subscriptionPromo->getResource()->save($subscriptionPromo);
        $afterSave = $this->subscriptionCustomerFactory->create();
        $afterSave->getResource()->load($afterSave, $subscriptionPromo->getSubscriptionCustomerId());
        $serializedAfterSave = $this->serializeHelper->serializeSubscriptionCustomer($afterSave);
        $subscriptionHistory->setAfterSave($serializedAfterSave);
        $subscriptionHistory->save();
        return $subscriptionPromo;
    }

    public function delete(SubscriptionPromoInterface $subscriptionPromo)
    {
        $subscriptionHistory = $this->subscriptionHistoryFactory->create();
        $beforeSave = $this->subscriptionCustomerFactory->create();
        $subscriptionCustomerId = $subscriptionPromo->getSubscriptionCustomerId();
        $beforeSave->getResource()->load($beforeSave, $subscriptionCustomerId);
        $serializedBeforeSave = $this->serializeHelper->serializeSubscriptionCustomer($beforeSave);
        $subscriptionHistory
            ->setSubscriptionCustomerId($subscriptionCustomerId)
            ->setBeforeSave($serializedBeforeSave);
        $currentCustomer = $this->customerSession->getCustomer();
        $admin = $this->authSession->getUser();
        if($currentCustomer && $currentCustomer->getId()){
            $subscriptionHistory->setCustomerId($currentCustomer->getId());
        } else if($admin && $admin->getId()){
            $subscriptionHistory->setAdminUserId($admin->getId());
        }
        $subscriptionPromo->getResource()->delete($subscriptionPromo);
        $afterSave = $this->subscriptionCustomerFactory->create();
        $afterSave->getResource()->load($afterSave, $subscriptionCustomerId);
        $serializedAfterSave = $this->serializeHelper->serializeSubscriptionCustomer($afterSave);
        $subscriptionHistory->setAfterSave($serializedAfterSave);
        $subscriptionHistory->save();

    }

    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->subscriptionPromoCollectionFactory->create();

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

    public function getListBySubscriptionCustomerId(int $subscriptionCustomerId)
    {
        //TODO - sort for date
        $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('subscription_customer_id',$subscriptionCustomerId,'eq')
                ->create();
        return $this->getList($searchCriteria);
    }
}