<?php

namespace Vonnda\DeviceManager\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        if (version_compare($context->getVersion(), "1.1.0", "<")) {
            $this->reconfigureCustomerIdColumn($setup);
        }
        if (version_compare($context->getVersion(), "1.2.0", "<")) {
            $this->addSalesChannelToDeviceSchema($setup);
        }
        if (version_compare($context->getVersion(), "1.3.0", "<")) {
            $setup->startSetup();
            $setup->getConnection()->addColumn($setup->getTable('vonnda_devicemanagment_device'), 'is_serial_number_valid', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'nullable' => true,
                'default' => null,
                'unsigned' => true,
                'comment' => 'Is serial number valid'
            ]);
            $setup->endSetup();
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function reconfigureCustomerIdColumn(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->changeColumn(
            $setup->getTable('vonnda_devicemanagment_device'),
            'customer_id',
            'customer_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'nullable' => true,
                'default' => null,
                'unsigned' => true,
                'comment' => 'Customer Id'
            ]);
        $setup->getConnection()->addForeignKey(
            $setup->getFkName('vonnda_devicemanagment_device',
                              'customer_id',
                              'customer_entity', 
                              'entity_id'),
            $setup->getTable('vonnda_devicemanagment_device'),
            'customer_id',
            $setup->getTable('customer_entity'),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
        );
        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function addSalesChannelToDeviceSchema(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->addColumn(
            $setup->getTable('vonnda_devicemanagment_device'),
            'sales_channel',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Sales Channel'
            ]
        );
        $setup->endSetup();
    }
}
