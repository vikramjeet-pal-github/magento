<?php

namespace Vonnda\AlternativeOrigin\Api\Data;

interface AlternativeShippingOriginZonesInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{

    const POSTCODE = 'postcode';
    const REGION_ID = 'region_id';
    const ENTITY_ID = 'entity_id';
    const COUNTRY_ID = 'country_id';

    /**
     * Get entity_id
     * @return string|null
     */
    public function getEntityId();

    /**
     * Set entity_id
     * @param string $entityId
     * @return \Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesInterface
     */
    public function setEntityId($entityId);

    /**
     * Get country_id
     * @return string|null
     */
    public function getCountryId();

    /**
     * Set country_id
     * @param string $countryId
     * @return \Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesInterface
     */
    public function setCountryId($countryId);

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param \Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesExtensionInterface $extensionAttributes
    );

    /**
     * Get region_id
     * @return string|null
     */
    public function getRegionId();

    /**
     * Set region_id
     * @param string $regionId
     * @return \Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesInterface
     */
    public function setRegionId($regionId);

    /**
     * Get postcode
     * @return string|null
     */
    public function getPostcode();

    /**
     * Set postcode
     * @param string $postcode
     * @return \Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesInterface
     */
    public function setPostcode($postcode);
}
