<?php

namespace Vonnda\OrderTag\Model;

class OrderTag extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init('Vonnda\OrderTag\Model\ResourceModel\OrderTag');
    }
}
