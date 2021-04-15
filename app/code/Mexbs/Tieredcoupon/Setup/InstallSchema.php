<?php
namespace Mexbs\Tieredcoupon\Setup;

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
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        /**
         * Create table 'mexbs_tieredcoupon'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('mexbs_tieredcoupon')
        )->addColumn(
                'tieredcoupon_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Tieredcoupon Id'
            )->addColumn(
                'name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '255',
                [],
                'Name'
            )->addColumn(
                'code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '255',
                [],
                'Code'
            )->addColumn(
                'description',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '64k',
                [],
                'Description'
            )->addColumn(
                'is_active',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Is Active'
            )->addIndex(
                $installer->getIdxName('mexbs_tieredcoupon', ['code']),
                ['code']
            )->setComment(
                'Tiered Coupon'
            );

        $installer->getConnection()->createTable($table);


        /**
         * Create table 'mexbs_tieredcoupon_coupon'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('mexbs_tieredcoupon_coupon')
        )->addColumn(
                'tieredcoupon_coupon_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Tieredcoupon Coupon Id'
            )->addColumn(
                'tieredcoupon_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Tieredcoupon Id'
            )->addColumn(
                'coupon_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Coupon Id'
            )->addForeignKey(
                $installer->getFkName('mexbs_tieredcoupon_coupon', 'coupon_id', 'salesrule_coupon', 'coupon_id'),
                'coupon_id',
                $installer->getTable('salesrule_coupon'),
                'coupon_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->addForeignKey(
                $installer->getFkName('mexbs_tieredcoupon_coupon', 'tieredcoupon_id', 'mexbs_tieredcoupon', 'tieredcoupon_id'),
                'tieredcoupon_id',
                $installer->getTable('mexbs_tieredcoupon'),
                'tieredcoupon_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->addIndex(
                $setup->getIdxName(
                    'mexbs_tieredcoupon_coupon',
                    ['tieredcoupon_id', 'coupon_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['tieredcoupon_id', 'coupon_id'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )->setComment(
                'Tiered Coupon Coupon'
            );

        $installer->getConnection()->createTable($table);

        $installer->endSetup();

    }
}