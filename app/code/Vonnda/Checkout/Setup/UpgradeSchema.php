<?php

namespace Vonnda\Checkout\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * {@inheritdoc}
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.1', '>')) {
            if (!$installer->tableExists('vonnda_checkout_affirm_subscriptions')) {
                $tableName = $installer->getTable('vonnda_checkout_affirm_subscriptions');
                $table = $installer->getConnection()->newTable($tableName);
                $table->addColumn('id', Table::TYPE_BIGINT, null, [
                        Table::OPTION_IDENTITY => true,
                        Table::OPTION_NULLABLE => false,
                        Table::OPTION_PRIMARY => true,
                        Table::OPTION_UNSIGNED => true
                ], 'ID');
                $table->addColumn('order_id', Table::TYPE_BIGINT, 255, [
                        Table::OPTION_NULLABLE => false,
                        Table::OPTION_UNSIGNED => true
                ], 'Order ID');
                $table->addColumn('stripe_id', Table::TYPE_TEXT, 255, [
                        Table::OPTION_NULLABLE => false,
                ], 'Stripe Payment ID');
                $table->addColumn('stripe_customer', Table::TYPE_TEXT, 255, [
                        Table::OPTION_NULLABLE => false,
                ], 'Stripe Customer ID');
                $table->addColumn('address', Table::TYPE_TEXT, 65535, [
                        Table::OPTION_NULLABLE => true,
                ], 'Address');
                $table->addColumn('created_at', Table::TYPE_TIMESTAMP, null, [
                        Table::OPTION_NULLABLE => false,
                        Table::OPTION_DEFAULT => Table::TIMESTAMP_INIT
                ], 'Created At');
                $table->addColumn('updated_at', Table::TYPE_TIMESTAMP, null, [
                        Table::OPTION_NULLABLE => false,
                        Table::OPTION_DEFAULT => Table::TIMESTAMP_INIT_UPDATE
                ], 'Updated At');
                $table->setComment('Affirm Subscriptions Table');
                $installer->getConnection()->createTable($table);

                $indexName = $setup->getIdxName($tableName, ['order_id'], AdapterInterface::INDEX_TYPE_UNIQUE);
                $installer->getConnection()->addIndex($tableName, $indexName, ['order_id'], AdapterInterface::INDEX_TYPE_UNIQUE);
            }
        }

        $installer->endSetup();
    }
}
