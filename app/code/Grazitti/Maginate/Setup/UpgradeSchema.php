<?php
/**
 * Copyright © 2020 Grazitti. All rights reserved.
 */
namespace Grazitti\Maginate\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();
        
        if (version_compare($context->getVersion(), '1.0.0.0') < 0) {
            if (!$installer->tableExists('grazitti_error_logs')) {
                $table = $installer->getConnection()->newTable(
                    $installer->getTable('grazitti_error_logs')
                )
                    ->addColumn(
                        'log_id',
                        Table::TYPE_INTEGER,
                        null,
                        [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                           ],
                        'log_id'
                    )
                       ->addColumn(
                           'api_params',
                           Table::TYPE_TEXT,
                           null,
                           [
                           'identity' => false,
                           'unsigned' => true,
                           'nullable' => false,
                           'primary' => false
                           ],
                           'api_params'
                       )
                       ->addColumn(
                           'api_url',
                           Table::TYPE_TEXT,
                           null,
                           [
                           'identity' => false,
                           'unsigned' => true,
                           'nullable' => false,
                           'primary' => false
                           ],
                           'api_url'
                       )
                ->addColumn(
                    'created_time',
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'identity' => false,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => false
                           ],
                    'created_time'
                )
                ->addColumn(
                    'success',
                    Table::TYPE_TEXT,
                    null,
                    [
                        'identity' => false,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => false
                           ],
                    'success'
                )
                ->addColumn(
                    'response',
                    Table::TYPE_TEXT,
                    null,
                    [
                        'identity' => false,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => false
                           ],
                    'response'
                )
                ->addColumn(
                    'message',
                    Table::TYPE_TEXT,
                    null,
                    [
                        'identity' => false,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => false
                           ],
                    'message'
                )
                    ->setComment('Sync Table')
                    ->setOption('type', 'InnoDB')
                    ->setOption('charset', 'utf8');
                   $installer->getConnection()->createTable($table);
            }
        }
        
        if (version_compare($context->getVersion(), '1.0.0.2') < 0) {
            $tableName = $installer->getTable('grazitti_error_logs');
            $fullTextIntex = ['api_params', 'api_url', 'response', 'message'];
            $setup->getConnection()->addIndex(
                $tableName,
                $installer->getIdxName(
                    $tableName,
                    $fullTextIntex,
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
                ),
                $fullTextIntex,
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
            );
        }
        $installer->endSetup();
    }
}
