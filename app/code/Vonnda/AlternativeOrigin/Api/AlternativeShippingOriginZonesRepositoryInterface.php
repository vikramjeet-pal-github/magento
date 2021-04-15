<?php

namespace Vonnda\AlternativeOrigin\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface AlternativeShippingOriginZonesRepositoryInterface
{

    /**
     * Save alternative_shipping_origin_zones
     * @param \Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesInterface $alternativeShippingOriginZones
     * @return \Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesInterface $alternativeShippingOriginZones
    );

    /**
     * Retrieve alternative_shipping_origin_zones
     * @param string $entityId
     * @return \Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($entityId);

    /**
     * Retrieve alternative_shipping_origin_zones matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete alternative_shipping_origin_zones
     * @param \Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesInterface $alternativeShippingOriginZones
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesInterface $alternativeShippingOriginZones
    );

    /**
     * Delete alternative_shipping_origin_zones by ID
     * @param string $entityId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($entityId);
}
