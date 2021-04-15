<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace MLK\Core\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\UrlRewrite\Model\UrlRewriteFactory;
use Magento\UrlRewrite\Model\Storage\DbStorage as UrlRewriteStorage;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Upgrade data
 */
class UpgradeData implements UpgradeDataInterface
{
    const STORE_CODE_US = 'mlk_us_sv';
    
    /**
     * @var \Magento\Sales\Setup\SalesSetupFactory
     */
    protected $salesSetupFactory;

    /**
     * @var \Magento\UrlRewrite\Model\UrlRewriteFactory
     */
    protected $urlRewriteFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\UrlRewrite\Model\Storage
     */
    protected $urlRewriteStorage;

    /**
     * Init
     *
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        SalesSetupFactory $salesSetupFactory,
        UrlRewriteFactory $urlRewriteFactory,
        StoreManagerInterface $storeManager,
        UrlRewriteStorage $urlRewriteStorage
    )
    {
        $this->salesSetupFactory = $salesSetupFactory;
        $this->urlRewriteFactory = $urlRewriteFactory;
        $this->storeManager = $storeManager;
        $this->urlRewriteStorage = $urlRewriteStorage;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $this->addParentOrderIdAttributeToSalesOrder($setup);
        }

        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            $this->addUrlRewriteForReferrals($setup);
        }

        $setup->endSetup();
    }

    public function addParentOrderIdAttributeToSalesOrder(ModuleDataSetupInterface $setup)
    {
        $installer = $setup;
 
        $installer->startSetup();
 
        $salesSetup = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $installer]);
 
        $salesSetup->addAttribute(Order::ENTITY, 'parent_order_id', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            'visible' => false,
            'nullable' => true
        ]);
 
        $installer->endSetup();
    }

    public function addUrlRewriteForReferrals(ModuleDataSetupInterface $setup)
    {
        $targetPath = "mlk_core/customer/referrals";
        $requestPath = "referrals/";

        $dataArray = [
            "target_path" => $targetPath
        ];

        $this->urlRewriteStorage->deleteByData($dataArray);

        $stores = $this->storeManager->getStores();

        foreach($stores as $store){
            if($store->getCode() === self::STORE_CODE_US){
                $urlRewriteModel = $this->urlRewriteFactory->create()
                    ->setStoreId($store->getId())
                    ->setIsSystem(false)
                    ->setIdPath(rand(1, 100000))
                    ->setTargetPath($targetPath)
                    ->setRequestPath($requestPath)
                    ->save();
            }
        }
    }
}