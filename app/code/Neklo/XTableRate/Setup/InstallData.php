<?php
/*
NOTICE OF LICENSE

This source file is subject to the NekloEULA that is bundled with this package in the file ICENSE.txt.

It is also available through the world-wide-web at this URL: http://store.neklo.com/LICENSE.txt

Copyright (c)  Neklo (http://store.neklo.com/)
*/


namespace Neklo\XTableRate\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate;
use Neklo\XTableRate\Helper\Config;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    /**
     * @var Tablerate
     */
    private $tablerate;

    /**
     * @var Config
     */
    private $configHelper;

    /**
     * InstallData constructor.
     * @param Tablerate $tablerate
     * @param Config $configHelper
     */
    public function __construct(
        Tablerate $tablerate,
        Config $configHelper
    ) {
        $this->tablerate = $tablerate;
        $this->configHelper = $configHelper;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $this->migrateData();

        $setup->endSetup();
    }

    private function migrateData()
    {
        $adapter = $this->tablerate->getConnection();
        $select = $adapter->select()->from($this->tablerate->getTable('shipping_tablerate'));
        $rates = $adapter->fetchAll($select);
        foreach ($rates as $key => $rate) {
            $rates[$key]['shipping_name'] = $this->configHelper->getShippingName($rate['website_id']);
        }

        $adapter->insertMultiple($this->tablerate->getMainTable(), $rates);
    }
}
