<?php
/**
 * Copyright © 2020 Graziiti. All rights reserved.
 */

namespace Grazitti\Maginate\Model;
     
use Magento\Framework\Model\AbstractModel;
     
class Data extends AbstractModel
{
    
    protected function _construct()
    {
        $this->_init(\Grazitti\Maginate\Model\ResourceModel\Data::class);
    }
}
