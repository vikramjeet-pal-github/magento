<?php
/**
 * Copyright © 2020 Grazitti. All rights reserved.
 */

namespace Grazitti\Maginate\Model\ResourceModel\Data;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Grazitti\Maginate\Model\Data::class,
            \Grazitti\Maginate\Model\ResourceModel\Data::class
        );
    }
}
