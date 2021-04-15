<?php

namespace Vonnda\CheckoutSurvey\Api\Data;

interface CheckoutSurveySearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get CheckoutSurvey list.
     * @return \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterface[]
     */
    public function getItems();

    /**
     * Set entity_id list.
     * @param \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
