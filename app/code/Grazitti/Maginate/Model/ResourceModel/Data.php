<?php
/**
 * Copyright © 2020 Grazitti. All rights reserved.
 */
namespace Grazitti\Maginate\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;
     
class Data extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('customer_sync_records', 'id');
    }
}
