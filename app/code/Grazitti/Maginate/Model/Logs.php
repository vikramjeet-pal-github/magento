<?php
/**
 * Copyright © 2020 Grazitti . All rights reserved.
 */

namespace Grazitti\Maginate\Model;

class Logs extends \Magento\Framework\Model\AbstractModel
{
    public function _construct()
    {
        $this->_init(\Grazitti\Maginate\Model\ResourceModel\Logs::class);
    }
}
