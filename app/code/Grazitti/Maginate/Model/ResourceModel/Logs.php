<?php
/**
 * Copyright © 2020 Grazitti. All rights reserved.
 */
namespace Grazitti\Maginate\Model\ResourceModel;

class Logs extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function _construct()
    {
        $this->_init("grazitti_error_logs", "log_id");
    }
}
