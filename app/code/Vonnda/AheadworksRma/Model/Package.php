<?php
namespace Vonnda\AheadworksRma\Model;

class Package extends \Magento\Framework\Model\AbstractModel
{

    protected $_eventPrefix = 'vonnda_aheadworksrma_package';

    protected function _construct()
    {
        $this->_init('Vonnda\AheadworksRma\Model\ResourceModel\Package');
    }

}