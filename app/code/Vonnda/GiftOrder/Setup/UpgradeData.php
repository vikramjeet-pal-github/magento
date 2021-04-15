<?php
/**
 * @copyright: Copyright © 2020 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\GiftOrder\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\UrlRewrite\Model\UrlRewriteFactory;
use Magento\UrlRewrite\Model\Storage\DbStorage as UrlRewriteStorage;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Upgrade data
 */
class UpgradeData implements UpgradeDataInterface
{

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
        UrlRewriteFactory $urlRewriteFactory,
        StoreManagerInterface $storeManager,
        UrlRewriteStorage $urlRewriteStorage
    )
    {
        $this->urlRewriteFactory = $urlRewriteFactory;
        $this->storeManager = $storeManager;
        $this->urlRewriteStorage = $urlRewriteStorage;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {

        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            $this->addUrlRewriteForGiftLanding($setup);
        }

        $setup->endSetup();
    }

    public function addUrlRewriteForGiftLanding(ModuleDataSetupInterface $setup)
    {
        $targetPath = "subscription/customer/giftlanding";
        $requestPath = "yay";

        $dataArray = [
            "target_path" => $targetPath
        ];

        $this->urlRewriteStorage->deleteByData($dataArray);

        $stores = $this->storeManager->getStores();

        foreach($stores as $store){
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