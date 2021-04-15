<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Model;

use Amasty\Shiprules\Api\Data\RuleInterface;
use Amasty\Shiprules\Api\RuleRepositoryInterface;
use Amasty\Shiprules\Model\ResourceModel\Rule as RuleResource;
use Amasty\Shiprules\Model\ResourceModel\Rule\Collection;
use Amasty\Shiprules\Model\ResourceModel\Rule\CollectionFactory;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Ui\Api\Data\BookmarkSearchResultsInterfaceFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RuleRepository implements RuleRepositoryInterface
{
    /**
     * @var BookmarkSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var RuleFactory
     */
    private $ruleFactory;

    /**
     * @var RuleResource
     */
    private $ruleResource;

    /**
     * Model data storage
     *
     * @var array
     */
    private $rules;

    /**
     * Model data storage
     *
     * @var array
     */
    private $loadedByParams = [];

    /**
     * @var CollectionFactory
     */
    private $ruleCollectionFactory;

    public function __construct(
        BookmarkSearchResultsInterfaceFactory $searchResultsFactory,
        RuleFactory $ruleFactory,
        RuleResource $ruleResource,
        CollectionFactory $ruleCollectionFactory
    ) {
        $this->searchResultsFactory = $searchResultsFactory;
        $this->ruleFactory = $ruleFactory;
        $this->ruleResource = $ruleResource;
        $this->ruleCollectionFactory = $ruleCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function save(RuleInterface $rule)
    {
        try {
            if ($rule->getRuleId()) {
                $rule = $this->getById($rule->getRuleId())->addData($rule->getData());
            }
            $this->ruleResource->save($rule);
            unset($this->rules[$rule->getRuleId()]);
        } catch (\Exception $e) {
            if ($rule->getRuleId()) {
                throw new CouldNotSaveException(
                    __(
                        'Unable to save rule with ID %1. Error: %2',
                        [$rule->getRuleId(), $e->getMessage()]
                    )
                );
            }
            throw new CouldNotSaveException(__('Unable to save new rule. Error: %1', $e->getMessage()));
        }

        return $rule;
    }

    /**
     * @inheritdoc
     */
    public function getById($ruleId)
    {
        if (!isset($this->rules[$ruleId])) {
            /** @var \Amasty\Shiprules\Model\Rule $rule */
            $rule = $this->ruleFactory->create();
            $this->ruleResource->load($rule, $ruleId);
            if (!$rule->getRuleId()) {
                throw new NoSuchEntityException(__('Rule with specified ID "%1" not found.', $ruleId));
            }
            $this->rules[$ruleId] = $rule;
        }

        return $this->rules[$ruleId];
    }

    /**
     * @inheritdoc
     */
    public function delete(RuleInterface $rule)
    {
        try {
            $this->ruleResource->delete($rule);
            unset($this->rules[$rule->getRuleId()]);
        } catch (\Exception $e) {
            if ($rule->getRuleId()) {
                throw new CouldNotDeleteException(
                    __(
                        'Unable to remove rule with ID %1. Error: %2',
                        [$rule->getRuleId(), $e->getMessage()]
                    )
                );
            }
            throw new CouldNotDeleteException(__('Unable to remove rule. Error: %1', $e->getMessage()));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById($ruleId)
    {
        $ruleModel = $this->getById($ruleId);
        $this->delete($ruleModel);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);

        /** @var \Amasty\Shiprules\Model\ResourceModel\Rule\Collection $ruleCollection */
        $ruleCollection = $this->ruleCollectionFactory->create();

        // Add filters from root filter group to the collection
        foreach ($searchCriteria->getFilterGroups() as $group) {
            $this->addFilterGroupToCollection($group, $ruleCollection);
        }

        $searchResults->setTotalCount($ruleCollection->getSize());
        $sortOrders = $searchCriteria->getSortOrders();

        if ($sortOrders) {
            $this->addOrderToCollection($sortOrders, $ruleCollection);
        }

        $ruleCollection->setCurPage($searchCriteria->getCurrentPage());
        $ruleCollection->setPageSize($searchCriteria->getPageSize());

        $rules = [];
        /** @var RuleInterface $rule */
        foreach ($ruleCollection->getItems() as $rule) {
            $rules[] = $this->getById($rule->getRuleId());
        }

        $searchResults->setItems($rules);

        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function getRulesByParams($store, $customerGroupId, $isAdmin)
    {
        $key = $store . '-' . $customerGroupId . '-' . (int) $isAdmin;

        if (!isset($this->loadedByParams[$key])) {
            $collection = $this->ruleCollectionFactory->create();
            $collection
                ->addActiveFilter()
                ->addStoreFilter([$store])
                ->addCustomerGroupFilter($customerGroupId)
                ->addDaysFilter()
                ->setOrder(RuleInterface::POS, 'asc');

            if ($isAdmin) {
                $collection->addFieldToFilter(RuleInterface::FOR_ADMIN, 1);
            }

            $this->loadedByParams[$key] = $collection->getItems();

            /** @var RuleInterface $rule */
            foreach ($this->loadedByParams[$key] as $rule) {
                $this->rules[$rule->getRuleId()] = $rule;
            }
        }

        return $this->loadedByParams[$key];
    }

    /**
     * Helper function that adds a FilterGroup to the collection.
     *
     * @param FilterGroup $filterGroup
     * @param Collection  $ruleCollection
     *
     * @return void
     */
    private function addFilterGroupToCollection(FilterGroup $filterGroup, Collection $ruleCollection)
    {
        foreach ($filterGroup->getFilters() as $filter) {
            $condition = $filter->getConditionType() ?: 'eq';
            $ruleCollection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
        }
    }

    /**
     * Helper function that adds a SortOrder to the collection.
     *
     * @param SortOrder[] $sortOrders
     * @param Collection  $ruleCollection
     *
     * @return void
     */
    private function addOrderToCollection($sortOrders, Collection $ruleCollection)
    {
        /** @var SortOrder $sortOrder */
        foreach ($sortOrders as $sortOrder) {
            $field = $sortOrder->getField();
            $ruleCollection->addOrder(
                $field,
                ($sortOrder->getDirection() == SortOrder::SORT_DESC) ? SortOrder::SORT_DESC : SortOrder::SORT_ASC
            );
        }
    }
}
