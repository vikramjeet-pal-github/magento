<?php
/*
NOTICE OF LICENSE

This source file is subject to the NekloEULA that is bundled with this package in the file ICENSE.txt.

It is also available through the world-wide-web at this URL: http://store.neklo.com/LICENSE.txt

Copyright (c)  Neklo (http://store.neklo.com/)
*/


namespace Neklo\XTableRate\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Neklo\XTableRate\Api\Data\TablerateInterface;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * @var ModuleContextInterface
     */
    private $context;

    /**
     * @var SchemaSetupInterface
     */
    private $installer;

    /**
     * Installs DB schema for a module
     *
     * @param  SchemaSetupInterface   $setup
     * @param  ModuleContextInterface $context
     *
     * @return void
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $this->installer = $setup;
        $this->context = $context;
        $this->installer->startSetup();
        $this->createTable();
        $this->installer->endSetup();
    }

    private function createTable()
    {
        $chartTable = $this->installer->getTable('neklo_xtablerate');

        $uniqueIndexFields = [
            TablerateInterface::WEBSITE_ID,
            TablerateInterface::DEST_COUNTRY_ID,
            TablerateInterface::DEST_REGION_ID,
            TablerateInterface::DEST_ZIP,
            TablerateInterface::CONDITION_NAME,
            TablerateInterface::CONDITION_VALUE,
            TablerateInterface::SHIPPING_NAME,
        ];

        /**
         * Create table 'neklo_reviewreminder_reminder'
         */
        $table = $this->installer->getConnection()
            ->newTable($chartTable)
            ->addColumn(
                TablerateInterface::ENTITY_ID,
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary'  => true
                ],
                'Primary key'
            )
            ->addColumn(
                TablerateInterface::WEBSITE_ID,
                Table::TYPE_INTEGER,
                null,
                [
                    'nullable' => false,
                    'default'  => 0,
                ],
                'Website Id'
            )
            ->addColumn(
                TablerateInterface::DEST_COUNTRY_ID,
                Table::TYPE_TEXT,
                4,
                [
                    'nullable' => false,
                    'default'  => '0',
                ],
                'Destination coutry ISO/2 or ISO/3 code'
            )
            ->addColumn(
                TablerateInterface::DEST_REGION_ID,
                Table::TYPE_INTEGER,
                null,
                [
                    'nullable' => false,
                    'default'  => 0,
                ],
                'Destination Region Id'
            )
            ->addColumn(
                TablerateInterface::DEST_ZIP,
                Table::TYPE_TEXT,
                10,
                [
                    'nullable' => false,
                    'default'  => '*',
                ],
                'Destination Post Code (Zip)'
            )
            ->addColumn(
                TablerateInterface::SHIPPING_NAME,
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => false,
                    'default'  => '',
                ],
                'Shipping name'
            )
            ->addColumn(
                TablerateInterface::CONDITION_NAME,
                Table::TYPE_TEXT,
                20,
                ['nullable' => false],
                'Rate Condition name'
            )
            ->addColumn(
                TablerateInterface::CONDITION_VALUE,
                Table::TYPE_DECIMAL,
                [12, 4],
                [
                    'nullable' => false,
                    'default'  => '0.0000',
                ],
                'Rate condition value'
            )
            ->addColumn(
                TablerateInterface::PRICE,
                Table::TYPE_DECIMAL,
                [12, 4],
                [
                    'nullable' => false,
                    'default'  => '0.0000',
                ],
                'Price'
            )
            ->addColumn(
                TablerateInterface::COST,
                Table::TYPE_DECIMAL,
                [12, 4],
                [
                    'nullable' => false,
                    'default'  => '0.0000',
                ],
                'Cost'
            )
            ->addIndex(
                $this->installer->getIdxName(
                    $chartTable,
                    $uniqueIndexFields,
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                $uniqueIndexFields,
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->setComment('Neklo Extended Tablerate');

        $this->installer->getConnection()->createTable($table);
    }
}
