<?php
namespace Vonnda\AheadworksRma\Model\ResourceModel;

class Package extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    protected function _construct()
    {
        $this->_init('aw_rma_request_packages', 'package_id');
    }

}