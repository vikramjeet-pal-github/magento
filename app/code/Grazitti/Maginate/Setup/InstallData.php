<?php
/**
 * Copyright © 2020 Grazitti. All rights reserved.
 */

namespace Grazitti\Maginate\Setup;

use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Entity\Attribute\Set as AttributeSet;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Quote\Setup\QuoteSetupFactory;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Eav\Model\Entity\Attribute\Set;

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
     * Category setup factory
     *
     * @var CategorySetupFactory
     */
    protected $categorySetupFactory;

    /**
     * Quote setup factory
     *
     * @var QuoteSetupFactory
     */
    protected $quoteSetupFactory;

    /**
     * Sales setup factory
     *
     * @var SalesSetupFactory
     */
    protected $salesSetupFactory;

    /**
     * Init
     *
     * @param CategorySetupFactory $categorySetupFactory
     * @param SalesSetupFactory $salesSetupFactory
     */
    public function __construct(
        SalesSetupFactory $salesSetupFactory,
        CustomerSetupFactory $customerSetupFactory,
        AttributeSetFactory $attributeSetFactory,
        EavSetupFactory $eavSetupFactory,
        Set $set
    ) {
        $this->salesSetupFactory = $salesSetupFactory;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->_customerSetupFactory = $eavSetupFactory;
        $this->_attributeSetFactory = $set;
    }
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var \Magento\Sales\Setup\SalesSetup $salesSetup */
        $salesSetup = $this->salesSetupFactory->create(['setup' => $setup]);

        /**
         * Remove previous attributes
         */
        $attributes =       ['sync_with_marketo'];
        foreach ($attributes as $attr_to_remove) {
            $salesSetup->removeAttribute(\Magento\Sales\Model\Order::ENTITY, $attr_to_remove);
        }
        /**
         * Add 'NEW_ATTRIBUTE' attributes for order
         */
        $options = ['type' => 'varchar', 'visible' => false, 'required' => false];
        $salesSetup->addAttribute('order', 'sync_with_marketo', $options);
    }
}
