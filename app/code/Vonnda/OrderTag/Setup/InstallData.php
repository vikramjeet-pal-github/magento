<?php

namespace Vonnda\OrderTag\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Setup\SalesSetupFactory;

class InstallData implements InstallDataInterface
{
    /**
     * Sales setup factory
     *
     * @var SalesSetupFactory
     */
    protected $salesSetupFactory;

    /**
     * InstallData constructor.
     * @param SalesSetupFactory $salesSetupFactory
     */
    public function __construct(SalesSetupFactory $salesSetupFactory)
    {
        $this->salesSetupFactory = $salesSetupFactory;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $salesSetup = $this->salesSetupFactory->create(['setup' => $setup]);

        $options = [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            'length' => 1,
            'visible' => false,
            'required' => true
        ];

        $salesSetup->addAttribute(Order::ENTITY, 'order_tag_id', $options);

        $data = [
            'label' => 'Default',
            'visible' => true,
            'frontend_default' => 1
        ];
        $setup->getConnection()->insertForce($setup->getTable('sales_order_tags'), $data);

        $setup->endSetup();
    }
}
