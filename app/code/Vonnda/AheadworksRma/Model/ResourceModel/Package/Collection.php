<?php
namespace Vonnda\AheadworksRma\Model\ResourceModel\Package;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'package_id';
    protected $_eventPrefix = 'vonnda_aheadworksrma_package_collection';
    protected $_eventObject = 'package_collection';

    protected function _construct()
    {
        $this->_init('Vonnda\AheadworksRma\Model\Package', 'Vonnda\AheadworksRma\Model\ResourceModel\Package');
    }

}