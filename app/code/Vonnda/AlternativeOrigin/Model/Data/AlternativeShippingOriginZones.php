<?php

namespace Vonnda\AlternativeOrigin\Model\Data;

use Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesInterface;

class AlternativeShippingOriginZones extends \Magento\Framework\Api\AbstractExtensibleObject implements AlternativeShippingOriginZonesInterface
{

    /**
     * Get entity_id
     * @return string|null
     */
    public function getEntityId()
    {
        return $this->_get(self::ENTITY_ID);
    }

    /**
     * Set entity_id
     * @param string $entityId
     * @return \Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesInterface
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * Get country_id
     * @return string|null
     */
    public function getCountryId()
    {
        return $this->_get(self::COUNTRY_ID);
    }

    /**
     * Set country_id
     * @param string $countryId
     * @return \Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesInterface
     */
    public function setCountryId($countryId)
    {
        return $this->setData(self::COUNTRY_ID, $countryId);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     * @param \Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }

    /**
     * Get region_id
     * @return string|null
     */
    public function getRegionId()
    {
        return $this->_get(self::REGION_ID);
    }

    /**
     * Set region_id
     * @param string $regionId
     * @return \Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesInterface
     */
    public function setRegionId($regionId)
    {
        return $this->setData(self::REGION_ID, $regionId);
    }

    /**
     * Get postcode
     * @return string|null
     */
    public function getPostcode()
    {
        return $this->_get(self::POSTCODE);
    }

    /**
     * Set postcode
     * @param string $postcode
     * @return \Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesInterface
     */
    public function setPostcode($postcode)
    {
        return $this->setData(self::POSTCODE, $postcode);
    }
}
