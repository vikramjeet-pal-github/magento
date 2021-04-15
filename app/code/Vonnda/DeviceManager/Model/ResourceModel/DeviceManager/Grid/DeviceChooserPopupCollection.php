<?php

namespace Vonnda\DeviceManager\Model\ResourceModel\DeviceManager\Grid;

use Vonnda\DeviceManager\Model\ResourceModel\DeviceManager\Collection;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;

class DeviceChooserPopupCollection extends Collection
{
    protected $customerRepository;
    
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        CustomerRepositoryInterface $customerRepository
        )
    {        
        $this->customerRepository = $customerRepository;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager);
    }

    protected function _renderFiltersBefore()
    {
        $joinTable = $this->getTable('customer_entity');
        $this->getSelect()->joinLeft($joinTable, 'main_table.customer_id = customer_entity.entity_id', ['email']);
        parent::_renderFiltersBefore();
    }

    /**
     * Add field filter to collection
     *
     * @see self::_getConditionSql for $condition
     *
     * @param string|array $field
     * @param null|string|array $condition
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if (is_array($field)) {
            $conditions = [];
            foreach ($field as $key => $value) {
                $conditions[] = $this->_translateCondition($value, isset($condition[$key]) ? $condition[$key] : null);
            }

            $resultCondition = '(' . implode(') ' . \Magento\Framework\DB\Select::SQL_OR . ' (', $conditions) . ')';
        } else {
            $resultCondition = $this->_translateCondition($field, $condition);
        }

        //Make sure we search on the main table for all fields not pulled from the join tables
        if($field !== "email"){
            $resultCondition = "`main_table`." . $resultCondition;
        }
        $this->_select->where($resultCondition, null, \Magento\Framework\DB\Select::TYPE_CONDITION);

        return $this;
    }
    
}
