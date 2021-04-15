<?php

namespace Vonnda\OrderTag\Model\ResourceModel;

class OrderTag extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function _construct()
    {
        $this->_init('sales_order_tags', 'entity_id');
    }
}
