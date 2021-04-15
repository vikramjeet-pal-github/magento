<?php

namespace Vonnda\OrderTag\Model\ResourceModel\OrderTag;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Identifier field name for collection items
     *
     * Can be used by collections with items without defined
     *
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Name prefix of events that are dispatched by model
     *
     * @var string
     */
    protected $_eventPrefix = 'sales_order_tags_collection';

    /**
     * Name of event parameter
     *
     * @var string
     */
    protected $_eventObject = 'order_tag_collection';

    public function _construct()
    {
        $this->_init(
            'Vonnda\OrderTag\Model\OrderTag',
            'Vonnda\OrderTag\Model\ResourceModel\OrderTag'
        );
    }
}
