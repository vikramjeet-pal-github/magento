<?php

namespace Vonnda\DeviceManager\Model\ResourceModel\DeviceManager;

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
            \Vonnda\DeviceManager\Model\DeviceManager::class,
            \Vonnda\DeviceManager\Model\ResourceModel\DeviceManager::class
        );
    }
}
