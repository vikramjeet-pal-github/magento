<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use \Magento\Framework\DB\Ddl\Table;

/**
 * phpcs:ignoreFile
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $this->addBackordersField($setup);
        }

        if (version_compare($context->getVersion(), '1.1.2', '<')) {
            $this->updateFieldToDecimal($setup, 'rate_percent');
            $this->updateFieldToDecimal($setup, 'handling');
        }

        if (version_compare($context->getVersion(), '2.0.0', '<')) {
            $this->addTimesField($setup, 'time_from', 'Time From');
            $this->addTimesField($setup, 'time_to', 'Time To');
            $this->addCouponFields($setup, 'coupon_disable', 'Coupon Disable');
            $this->addCouponFields($setup, 'discount_id_disable', 'Disable Discount ID');
            $this->updateExistCouponField($setup, 'discount_id');
            $this->addForAdminField($setup, 'for_admin');
        }

        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    protected function addBackordersField(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->addColumn(
            $setup->getTable('amasty_shiprules_rule'),
            'out_of_stock',
            [
                'type' => Table::TYPE_SMALLINT,
                'nullable' => false,
                'default' => 0,
                'comment' => 'Apply to backoerders'
            ]
        );
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param $nameOfField
     * @param string $comment
     */
    protected function addTimesField(SchemaSetupInterface $setup, $nameOfField, $comment = '')
    {
        $setup->getConnection()->addColumn(
            $setup->getTable('amasty_shiprules_rule'),
            $nameOfField,
            [
                'type' => Table::TYPE_INTEGER,
                'default' => null,
                'nullable' => true,
                'comment' => $comment
            ]
        );
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param $nameOfField
     * @param string $comment
     */
    protected function addCouponFields(SchemaSetupInterface $setup, $nameOfField, $comment = '')
    {
        $setup->getConnection()->addColumn(
            $setup->getTable('amasty_shiprules_rule'),
            $nameOfField,
            [
                'type' => Table::TYPE_TEXT,
                'default' => null,
                'nullable' => true,
                'comment' => $comment
            ]
        );
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param $field
     */
    protected function updateExistCouponField(SchemaSetupInterface $setup, $field)
    {
        $setup->getConnection()->changeColumn(
            $setup->getTable('amasty_shiprules_rule'),
            $field,
            $field,
            [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => false,
            ]
        );
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param $field
     */
    protected function addForAdminField(SchemaSetupInterface $setup, $field)
    {
        $setup->getConnection()->addColumn(
            $setup->getTable('amasty_shiprules_rule'),
            $field,
            [
                'type' => Table::TYPE_SMALLINT,
                'nullable' => false,
                'comment' => 'For Admin Rule'
            ]
        );
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param $field
     */
    private function updateFieldToDecimal(SchemaSetupInterface $setup, $field)
    {
        $setup->getConnection()->changeColumn(
            $setup->getTable('amasty_shiprules_rule'),
            $field,
            $field,
            [
                'type' => Table::TYPE_DECIMAL,
                'length' => '12,2',
                'nullable' => false,
                'unsigned' => true,
                'default' => '0.00'
            ]
        );
    }
}
