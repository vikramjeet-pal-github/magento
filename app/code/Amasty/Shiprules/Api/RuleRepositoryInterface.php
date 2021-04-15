<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Api;

/**
 * @api
 */
interface RuleRepositoryInterface
{
    /**
     * Save
     *
     * @param \Amasty\Shiprules\Api\Data\RuleInterface $rule
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function save(\Amasty\Shiprules\Api\Data\RuleInterface $rule);

    /**
     * Get by id
     *
     * @param int $ruleId
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($ruleId);

    /**
     * Delete
     *
     * @param \Amasty\Shiprules\Api\Data\RuleInterface $rule
     *
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(\Amasty\Shiprules\Api\Data\RuleInterface $rule);

    /**
     * Delete by id
     *
     * @param int $ruleId
     *
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById($ruleId);

    /**
     * Lists
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     *
     * @return \Magento\Framework\Api\SearchResultsInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * @param int $store
     * @param int $customerGroupId
     * @param bool $isAdmin
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface[]
     */
    public function getRulesByParams($store, $customerGroupId, $isAdmin);
}
