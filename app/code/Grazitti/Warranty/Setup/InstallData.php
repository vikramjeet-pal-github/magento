<?php
namespace Grazitti\Warranty\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Quote\Setup\QuoteSetupFactory;
use Magento\Sales\Model\Order;
 
/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;
 
    /**
     * @var QuoteSetupFactory
     */
    private $quoteSetupFactory;
 
    /**
     * @var SalesSetup
     */
    private $salesSetupFactory;
 
    /**
     * InstallData constructor.
     * @param EavSetupFactory $eavSetupFactory
     * @param QuoteSetupFactory $quoteSetupFactory
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        QuoteSetupFactory $quoteSetupFactory,
        SalesSetupFactory $salesSetupFactory
    )
    {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->quoteSetupFactory = $quoteSetupFactory;
        $this->salesSetupFactory = $salesSetupFactory;
    }
 
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
 
        /** @var QuoteSetup $quoteSetup */
        $quoteSetup = $this->quoteSetupFactory->create(['setup' => $setup]);
 
        /** @var SalesSetup $salesSetup */
        $salesSetup = $this->salesSetupFactory->create(['setup' => $setup]);

        $installer = $setup;

        $installer->startSetup();
 
        /**
         * Add attributes to the eav/attribute
         */
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'product_sku',
            [
                'type'                    => 'varchar',
                'label'                   => 'Product Sku',
                'input'                   => 'text',
                'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => true,
                'default'                 => '',
                'searchable'              => false,
                'filterable'              => false,
                'comparable'              => false,
                'visible_on_front'        => false,
                'used_in_product_listing' => false,
                'unique'                  => false,
               
            ]
        );
 
        $attributeSetId = $eavSetup->getDefaultAttributeSetId('catalog_product');
        $eavSetup->addAttributeToSet(
            'catalog_product',
            $attributeSetId,
            'General',
            'product_sku'
        );
 
        $attributeOptions = [
            'type'     => Table::TYPE_TEXT,
            'visible'  => true,
            'required' => false
        ];
        $quoteSetup->addAttribute('quote_item', 'product_sku', $attributeOptions);
        $salesSetup->addAttribute('order_item', 'product_sku', $attributeOptions);
        $salesSetup->addAttribute('order', 'product_sku', $attributeOptions);
        $salesSetup->addAttribute('order', 'order_shipment_status', $attributeOptions);
        $salesSetup->addAttribute('order', 'order_shipping_text', $attributeOptions);
        $salesSetup->addAttribute(Order::ENTITY, 'product_sku', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            'length'=> 255,
            'visible' => false,
            'nullable' => true
        ]);

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order_grid'),
            'product_sku',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'comment' =>'Product_sku'
            ]
        );

        $installer->endSetup();
        
    }
}