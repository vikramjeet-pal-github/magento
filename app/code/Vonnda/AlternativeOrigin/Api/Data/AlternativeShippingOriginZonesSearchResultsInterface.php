<?php

namespace Vonnda\AlternativeOrigin\Api\Data;

interface AlternativeShippingOriginZonesSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get alternative_shipping_origin_zones list.
     * @return \Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesInterface[]
     */
    public function getItems();

    /**
     * Set country_id list.
     * @param \Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
