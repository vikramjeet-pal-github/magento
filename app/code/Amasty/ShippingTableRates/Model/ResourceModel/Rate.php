<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_ShippingTableRates
 */


namespace Amasty\ShippingTableRates\Model\ResourceModel;

/**
 * Rate Resource
 */
class Rate extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('amasty_table_rate', 'id');
    }

    /**
     * @param int $methodId
     */
    public function deleteBy($methodId)
    {
        $this->getConnection()->delete($this->getMainTable(), 'method_id=' . (int)$methodId);
    }
}
