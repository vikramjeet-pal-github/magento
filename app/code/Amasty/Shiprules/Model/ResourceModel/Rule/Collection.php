<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Model\ResourceModel\Rule;

/**
 * Rules Collection
 */
class Collection extends \Amasty\CommonRules\Model\ResourceModel\Rule\Collection
{
    protected function _construct()
    {
        $this->_init(\Amasty\Shiprules\Model\Rule::class, \Amasty\Shiprules\Model\ResourceModel\Rule::class);
        $this->_setIdFieldName($this->getResource()->getIdFieldName());
    }
}
