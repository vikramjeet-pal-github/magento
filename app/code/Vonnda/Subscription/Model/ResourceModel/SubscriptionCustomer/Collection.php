<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Model\ResourceModel\SubscriptionCustomer;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Vonnda\Subscription\Model\SubscriptionCustomer',
            'Vonnda\Subscription\Model\ResourceModel\SubscriptionCustomer'
        );
    }

    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()->joinLeft(
            ['customerTable' => $this->getTable('customer_grid_flat')],
            'main_table.customer_id = customerTable.entity_id',
            [
                'name',
                'email',
                'shipping_full'
            ]
        );
    }
}
