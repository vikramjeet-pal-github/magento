<?php

namespace Vonnda\AlternativeOrigin\Model\ResourceModel;

class AlternativeShippingOriginZones extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('alternative_shipping_origin_zones', 'entity_id');
    }
}
