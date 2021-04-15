<?php

namespace Vonnda\CheckoutSurvey\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface CheckoutSurveyRepositoryInterface
{

    /**
     * Save CheckoutSurvey
     * @param \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterface $checkoutSurvey
     * @return \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterface $checkoutSurvey
    );

    /**
     * Retrieve CheckoutSurvey
     * @param string $entityId
     * @return \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($entityId);

    /**
     * Retrieve CheckoutSurvey matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveySearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete CheckoutSurvey
     * @param \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterface $checkoutSurvey
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterface $checkoutSurvey
    );

    /**
     * Delete CheckoutSurvey by ID
     * @param string $entityId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($entityId);
}
