<?php

namespace Vonnda\DeviceManager\Model\ResourceModel;

class DeviceManager extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('vonnda_devicemanagment_device', 'entity_id');
    }
}
