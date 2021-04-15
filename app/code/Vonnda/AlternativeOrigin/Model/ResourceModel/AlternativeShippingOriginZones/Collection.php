<?php

namespace Vonnda\AlternativeOrigin\Model\ResourceModel\AlternativeShippingOriginZones;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Vonnda\AlternativeOrigin\Model\AlternativeShippingOriginZones::class,
            \Vonnda\AlternativeOrigin\Model\ResourceModel\AlternativeShippingOriginZones::class
        );
    }
}
