<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        /**
         * Create table 'vonnda_subscription_customer'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('vonnda_subscription_customer'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Subscription Customer Id'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionCustomer::CUSTOMER_ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Customer ID'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionCustomer::SHIPPING_ADDRESS_ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null],
                'Shipping Address ID'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionCustomer::STATUS,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Status'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionCustomer::CREATED_AT,
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Created At'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionCustomer::UPDATED_AT,
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => true, 'default' => null],
                'Updated At'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionCustomer::LAST_ORDER,
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => true, 'default' => null],
                'Last Order'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionCustomer::NEXT_ORDER,
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => true, 'default' => null],
                'Next Order'
            )
            ->addIndex(
                $installer->getIdxName('vonnda_subscription_customer', ['customer_id']),
                ['customer_id']
            )
            ->addForeignKey(
                $installer->getFkName('vonnda_subscription_customer', 
                                      \Vonnda\Subscription\Model\SubscriptionCustomer::CUSTOMER_ID, 
                                      'customer_entity', 
                                      'entity_id'),
                \Vonnda\Subscription\Model\SubscriptionCustomer::CUSTOMER_ID,
                $installer->getTable('customer_entity'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_NO_ACTION
            )
            ->addForeignKey(
                $installer->getFkName('vonnda_subscription_customer', 
                                      \Vonnda\Subscription\Model\SubscriptionCustomer::SHIPPING_ADDRESS_ID, 
                                      'customer_address_entity', 
                                      'entity_id'),
                \Vonnda\Subscription\Model\SubscriptionCustomer::SHIPPING_ADDRESS_ID,
                $installer->getTable('customer_address_entity'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
            )
            ->setComment('Subscription Customer');
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'vonnda_subscription_order'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('vonnda_subscription_order'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Subscription Order Id'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionOrder::SUBSCRIPTION_CUSTOMER_ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true],
                'Subscription Customer ID'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionOrder::ORDER_ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => null],
                'Order ID'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionOrder::STATUS,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Status'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionOrder::ERROR_MESSAGE,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['default' => null],
                'Error Message'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionOrder::CREATED_AT,
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Created At'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionOrder::UPDATED_AT,
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => true, 'default' => null],
                'Updated At'
            )
            ->addIndex(
                $installer->getIdxName('vonnda_subscription_order', [\Vonnda\Subscription\Model\SubscriptionOrder::SUBSCRIPTION_CUSTOMER_ID]),
                [\Vonnda\Subscription\Model\SubscriptionOrder::SUBSCRIPTION_CUSTOMER_ID]
            )
            ->addForeignKey(
                $installer->getFkName('vonnda_subscription_order',
                                      \Vonnda\Subscription\Model\SubscriptionOrder::SUBSCRIPTION_CUSTOMER_ID, 
                                      'vonnda_subscription_customer', 
                                      'id'),
                \Vonnda\Subscription\Model\SubscriptionOrder::SUBSCRIPTION_CUSTOMER_ID,
                $installer->getTable('vonnda_subscription_customer'),
                'id',
                \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
            )
            ->addForeignKey(
                $installer->getFkName('vonnda_subscription_order', 
                                      \Vonnda\Subscription\Model\SubscriptionOrder::ORDER_ID, 
                                      'sales_order', 
                                      'entity_id'),
                \Vonnda\Subscription\Model\SubscriptionOrder::ORDER_ID,
                $installer->getTable('sales_order'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Subscription Order');
        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
