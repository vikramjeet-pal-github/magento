<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * Upgrade the extension
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var EavSetup
     */
    protected $eavSetup;

    /**
     * UpgradeSchema constructor.
     * @param EavSetup $eavSetup
     */
    public function __construct(
        EavSetup $eavSetup
    ) {
        $this->eavSetup = $eavSetup;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $this->addEmailToVonndaSubscriptionCustomer($setup);
        }

        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            $this->addTieredPlanTable($setup);
            $this->addPlanIdToVonndaSubscriptionCustomer($setup);
        }

        if (version_compare($context->getVersion(), '1.3.0', '<')) {
            $this->addPlanForeignKeyToVonndaSubscriptionCustomer($setup);
        }

        if (version_compare($context->getVersion(), '1.4.0', '<')) {
            $this->addSubscriptionProductTable($setup);
        }

        if (version_compare($context->getVersion(), '1.5.0', '<')) {
            $this->addSubscriptionPaymentTable($setup);
        }

        if (version_compare($context->getVersion(), '1.6.0', '<')) {
            $this->addBillingAddressIdAndPaymentCodeToVonndaSubscriptionPayment($setup);
            $this->addBillingAddressForeignKeyToVonndaSubscriptionPayment($setup);
        }

        if (version_compare($context->getVersion(), '1.7.0', '<')) {
            $this->addSubscriptionPromoTable($setup);
            $this->addDefaultPromosToVonndaSubscriptionPlan($setup);
        }

        if (version_compare($context->getVersion(), '1.8.0', '<')) {
            $this->addSubscriptionHistoryTable($setup);
        }

        if (version_compare($context->getVersion(), '1.9.0', '<')) {
            $this->addErrorMessageToSubscriptionCustomer($setup);
            $this->dropEmailColumnFromSubscriptionCustomer($setup);
            $this->renamePromoCodeToCouponCode($setup);
            $this->renameDefaultPromoCodesToPromoIds($setup);
        }

        if (version_compare($context->getVersion(), '2.0.0', '<')) {
            $this->addDeviceManagerIdToSubscriptionCustomer($setup);
        }

        if (version_compare($context->getVersion(), '2.2.0', '<')) {
            $this->changeDurationColumnOnSubscriptionPlan($setup);
        }

        if (version_compare($context->getVersion(), '2.3.0', '<')) {
            $this->addNumberOfFreeShipmentsToSubscriptionPlan($setup);
        }

        if (version_compare($context->getVersion(), '2.4.0', '<')) {
            $this->addPriceOverrideToSubscriptionProduct($setup);
        }

        if (version_compare($context->getVersion(), '2.5.0', '<')) {
            $this->addStateToSubscriptionCustomer($setup);
        }

        if (version_compare($context->getVersion(), '2.6.0', '<')) {
            $this->dropSubscriptionCustomerIdFromSubscriptionPayment($setup);
            $this->addSubscriptionPaymentIdToSubscriptionCustomer($setup);
        }

        if (version_compare($context->getVersion(), '2.7.0', '<')) {
            $this->dropBillingAddressIdFromSubscriptionPayment($setup);
            $this->addBillingAddressToSubscriptionPayment($setup);
        }
        
        if (version_compare($context->getVersion(), '2.8.0', '<')) {
            $this->addParentOrderIdToSubscriptionCustomer($setup);
            $this->addDeviceSkuToSubscriptionPlan($setup);
        }

        if (version_compare($context->getVersion(), '2.9.0', '<')) {
            $this->addCancelReasonToSubscriptionCustomer($setup);
        }

        if (version_compare($context->getVersion(), '2.10.0', '<')) {
            $this->updatePlansForLegacySupport($setup);
        }

        if (version_compare($context->getVersion(), '2.11.0', '<')) {
            $this->addIdentifierToPlans($setup);
        }

        if (version_compare($context->getVersion(), '2.12.0', '<')) {
            $this->addShippingMethodAndCostToSubscriptionCustomer($setup);
        }

        if (version_compare($context->getVersion(), '2.13.0', '<')) {
            $this->addStoreIdToSubscriptionPlan($setup);
        }

        if (version_compare($context->getVersion(), '2.15.0', '<')) {
            $this->addFallbackPlanToSubscriptionPlan($setup);
        }

        if (version_compare($context->getVersion(), '2.18.0', '<')) {
            $this->changeDefaultUpdatedAtForSubModels($setup);
        }

        if (version_compare($context->getVersion(), '3.0.0', '<')) {
            $this->updateSubscriptionPaymentTable($setup);
        }
        if (version_compare($context->getVersion(), "3.1.0", "<")) {
            $this->addGiftOrderColumns($setup);
            $this->addGiftRecipientEmailColumn($setup);
            $this->addGiftedColumn($setup);
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function addGiftOrderColumns(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->addColumn($setup->getTable('quote'), 
        'gift_order', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            'nullable' => true,
            'default' => null,
            'unsigned' => true,
            'comment' => 'Is quote a gift'
        ]);
        $setup->getConnection()->addColumn($setup->getTable('sales_order'), 
        'gift_order', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            'nullable' => true,
            'default' => null,
            'unsigned' => true,
            'comment' => 'Is order a gift'
        ]);
        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function addGiftRecipientEmailColumn(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->addColumn($setup->getTable('quote_address'), 
        'gift_recipient_email', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            'nullable' => true,
            'default' => null,
            'unsigned' => true,
            'comment' => 'Gift recipient email'
        ]);
        $setup->getConnection()->addColumn($setup->getTable('sales_order_address'), 
        'gift_recipient_email', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            'nullable' => true,
            'default' => null,
            'unsigned' => true,
            'comment' => 'Gift recipient email'
        ]);
        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function addGiftedColumn(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->addColumn($setup->getTable('vonnda_subscription_customer'), 
        'gifted', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            'nullable' => true,
            'default' => false,
            'unsigned' => true,
            'comment' => 'Was device gifted'
        ]);
        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function addEmailToVonndaSubscriptionCustomer(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->addColumn(
            $setup->getTable('vonnda_subscription_customer'),
            'email',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Email'
            ]
        );
        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function addTieredPlanTable(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        
        /**
         * Create table 'vonnda_subscription_plan'
         */
        $table = $setup->getConnection()
            ->newTable($setup->getTable('vonnda_subscription_plan'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Subscription Plan Id'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionPlan::TITLE,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Title'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionPlan::SHORT_DESCRIPTION,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => true],
                'Short Description'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionPlan::LONG_DESCRIPTION,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => true],
                'Long Description'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionPlan::MORE_INFO,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => true],
                'More Info'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionPlan::FREQUENCY_UNITS,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Frequency Units'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionPlan::FREQUENCY,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Frequency'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionPlan::TRIGGER_SKU,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Trigger Sku'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionPlan::SORT_ORDER,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Sort Order'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionPlan::DURATION,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Duration'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionPlan::STATUS,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Status'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionPlan::VISIBLE,
                \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false],
                'Visible'
            )
            ->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Created At'
            )
            ->addColumn(
                'updated_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => true, 'default' => null],
                'Updated At'
            )
            ->addIndex(
                $setup->getIdxName('vonnda_subscription_plan', [\Vonnda\Subscription\Model\SubscriptionPlan::TRIGGER_SKU]),
                [\Vonnda\Subscription\Model\SubscriptionPlan::TRIGGER_SKU]
            )
            ->setComment('Subscription Plan');
        $setup->getConnection()->createTable($table);

        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function addPlanIdToVonndaSubscriptionCustomer(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->addColumn(
            $setup->getTable('vonnda_subscription_customer'),
            'subscription_plan_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'nullable' => true,
                'unsigned' => true,
                'comment' => 'Subscription Plan Id'
            ]
        );
        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function addPlanForeignKeyToVonndaSubscriptionCustomer(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->addForeignKey(
            $setup->getFkName('vonnda_subscription_customer', 'subscription_plan_id', 'vonnda_subscription_plan', 'id'),
            $setup->getTable('vonnda_subscription_customer'),
            'subscription_plan_id',
            $setup->getTable('vonnda_subscription_plan'),
            'id',
            \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
        );
        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function addSubscriptionProductTable(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        
        /**
         * Create table 'vonnda_subscription_product'
         */
        $table = $setup->getConnection()
            ->newTable($setup->getTable('vonnda_subscription_product'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Subscription Product Id'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionProduct::SUBSCRIPTION_PLAN_ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Subscription Plan Id'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionProduct::PRODUCT_ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Product Id'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionProduct::QTY,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Qty'
            )
            ->addIndex(
                $setup->getIdxName('vonnda_subscription_product', [\Vonnda\Subscription\Model\SubscriptionProduct::PRODUCT_ID]),
                [\Vonnda\Subscription\Model\SubscriptionProduct::PRODUCT_ID]
            )
            ->addIndex(
                $setup->getIdxName('vonnda_subscription_product', [\Vonnda\Subscription\Model\SubscriptionProduct::SUBSCRIPTION_PLAN_ID]),
                [\Vonnda\Subscription\Model\SubscriptionProduct::SUBSCRIPTION_PLAN_ID]
            )
            ->addForeignKey(
                $setup->getFkName('vonnda_subscription_order',
                                  \Vonnda\Subscription\Model\SubscriptionProduct::PRODUCT_ID, 
                                  'catalog_product_entity', 
                                  'entity_id'),
                \Vonnda\Subscription\Model\SubscriptionProduct::PRODUCT_ID,
                $setup->getTable('catalog_product_entity'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
            )
            ->addForeignKey(
                $setup->getFkName('vonnda_subscription_product', 
                                  \Vonnda\Subscription\Model\SubscriptionProduct::SUBSCRIPTION_PLAN_ID, 
                                  'vonnda_subscription_plan', 
                                  'id'),
                \Vonnda\Subscription\Model\SubscriptionProduct::SUBSCRIPTION_PLAN_ID,
                $setup->getTable('vonnda_subscription_plan'),
                'id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Subscription Product');
        $setup->getConnection()->createTable($table);

        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function addSubscriptionPaymentTable(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        
        /**
         * Create table 'vonnda_subscription_payment'
         */
        $table = $setup->getConnection()
            ->newTable($setup->getTable('vonnda_subscription_payment'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Subscription Payment Id'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionPayment::SUBSCRIPTION_CUSTOMER_ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Subscription Customer Id'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionPayment::STRIPE_CUSTOMER_ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Stripe Customer Id'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionPayment::PAYMENT_ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Payment Id'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionCustomer::STATUS,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Status'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionPayment::EXPIRATION_DATE,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => true, 'default' => null],
                'Expiration Date'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionPayment::CREATED_AT,
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Created At'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionPayment::UPDATED_AT,
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => true, 'default' => null],
                'Updated At'
            )
            ->addIndex(
                $setup->getIdxName('vonnda_subscription_payment', [\Vonnda\Subscription\Model\SubscriptionPayment::SUBSCRIPTION_CUSTOMER_ID]),
                [\Vonnda\Subscription\Model\SubscriptionPayment::SUBSCRIPTION_CUSTOMER_ID]
            )
            ->addForeignKey(
                $setup->getFkName('vonnda_subscription_payment',
                                  \Vonnda\Subscription\Model\SubscriptionPayment::SUBSCRIPTION_CUSTOMER_ID, 
                                  'vonnda_subscription_customer', 
                                  'id'),
                \Vonnda\Subscription\Model\SubscriptionPayment::SUBSCRIPTION_CUSTOMER_ID,
                $setup->getTable('vonnda_subscription_customer'),
                'id',
                \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
            )
            ->addForeignKey(
                $setup->getFkName('vonnda_subscription_payment',
                                  \Vonnda\Subscription\Model\SubscriptionPayment::STRIPE_CUSTOMER_ID, 
                                  'cryozonic_stripe_customers',
                                  'id'),
                \Vonnda\Subscription\Model\SubscriptionPayment::STRIPE_CUSTOMER_ID,
                $setup->getTable('cryozonic_stripe_customers'),
                'id',
                \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
            )
            ->addForeignKey(
                $setup->getFkName('vonnda_subscription_payment', 
                                  \Vonnda\Subscription\Model\SubscriptionPayment::PAYMENT_ID, 
                                  'sales_order_payment', 
                                  'entity_id'),
                \Vonnda\Subscription\Model\SubscriptionPayment::PAYMENT_ID,
                $setup->getTable('sales_order_payment'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
            )
            ->setComment('Subscription Payment');
        $setup->getConnection()->createTable($table);

        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function addBillingAddressIdAndPaymentCodeToVonndaSubscriptionPayment(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->addColumn(
                $setup->getTable('vonnda_subscription_payment'),
                \Vonnda\Subscription\Model\SubscriptionPayment::BILLING_ADDRESS_ID,
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'nullable' => true,
                    'unsigned' => true,
                    'comment' => 'Billing Address Id'
                ]
                );

        $setup->getConnection()->addColumn(
                $setup->getTable('vonnda_subscription_payment'),
                \Vonnda\Subscription\Model\SubscriptionPayment::PAYMENT_CODE,
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'unsigned' => true,
                    'comment' => 'Payment Code'
                ]
                );
        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function addBillingAddressForeignKeyToVonndaSubscriptionPayment(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->addForeignKey(
            $setup->getFkName('vonnda_subscription_payment', 'billing_address_id', 'customer_address_entity', 'entity_id'),
            $setup->getTable('vonnda_subscription_payment'),
            'billing_address_id',
            $setup->getTable('customer_address_entity'),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
        );
        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function addSubscriptionPromoTable(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        
        /**
         * Create table 'vonnda_subscription_promo'
         */
        $table = $setup->getConnection()
            ->newTable($setup->getTable('vonnda_subscription_promo'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Subscription Promo Id'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionPromo::SUBSCRIPTION_CUSTOMER_ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Subscription Customer Id'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionPromo::SUBSCRIPTION_ORDER_ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Subscription Order Id'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionPromo::PROMO_CODE,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Promo Code'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionPromo::ERROR_MESSAGE,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => true, 'default' => null],
                'Error Message'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionPromo::USED_STATUS,
                \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false, 'default' => false],
                'Used Status'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionPromo::CREATED_AT,
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Created At'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionPromo::USED_AT,
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => true, 'default' => null],
                'Used At'
            )
            ->addIndex(
                $setup->getIdxName('vonnda_subscription_promo', [\Vonnda\Subscription\Model\SubscriptionPromo::SUBSCRIPTION_CUSTOMER_ID]),
                [\Vonnda\Subscription\Model\SubscriptionPromo::SUBSCRIPTION_CUSTOMER_ID]
            )
            ->addForeignKey(
                $setup->getFkName('vonnda_subscription_promo',
                                  \Vonnda\Subscription\Model\SubscriptionPromo::SUBSCRIPTION_CUSTOMER_ID, 
                                  'vonnda_subscription_customer', 
                                  'id'),
                \Vonnda\Subscription\Model\SubscriptionPromo::SUBSCRIPTION_CUSTOMER_ID,
                $setup->getTable('vonnda_subscription_customer'),
                'id',
                \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
            )
            ->addForeignKey(
                $setup->getFkName('vonnda_subscription_promo',
                                  \Vonnda\Subscription\Model\SubscriptionPromo::SUBSCRIPTION_ORDER_ID, 
                                  'vonnda_subscription_order', 
                                  'id'),
                \Vonnda\Subscription\Model\SubscriptionPromo::SUBSCRIPTION_ORDER_ID,
                $setup->getTable('vonnda_subscription_order'),
                'id',
                \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
            )
            ->setComment('Subscription Promo');
        $setup->getConnection()->createTable($table);

        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function addDefaultPromosToVonndaSubscriptionPlan(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->addColumn(
            $setup->getTable('vonnda_subscription_plan'),
            \Vonnda\Subscription\Model\SubscriptionPlan::DEFAULT_PROMO_CODES,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Default Promo Codes'
            ]
        );
        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function addSubscriptionHistoryTable(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        
        /**
         * Create table 'vonnda_subscription_history'
         */
        $table = $setup->getConnection()
            ->newTable($setup->getTable('vonnda_subscription_history'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Subscription Hisotry Id'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionHistory::SUBSCRIPTION_CUSTOMER_ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Subscription Customer Id'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionHistory::CUSTOMER_ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Customer Id'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionHistory::ADMIN_USER_ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Admin User Id'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionHistory::BEFORE_SAVE,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => true],
                'Serialized Objects Before Save'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionHistory::AFTER_SAVE,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => true],
                'Serialized Objects Before Save'
            )
            ->addColumn(
                \Vonnda\Subscription\Model\SubscriptionHistory::CREATED_AT,
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Created At'
            )
            ->addIndex(
                $setup->getIdxName('vonnda_subscription_history', [\Vonnda\Subscription\Model\SubscriptionHistory::SUBSCRIPTION_CUSTOMER_ID]),
                [\Vonnda\Subscription\Model\SubscriptionHistory::SUBSCRIPTION_CUSTOMER_ID]
            )
            ->addForeignKey(
                $setup->getFkName('vonnda_subscription_history',
                                  \Vonnda\Subscription\Model\SubscriptionHistory::SUBSCRIPTION_CUSTOMER_ID, 
                                  'vonnda_subscription_customer', 
                                  'id'),
                \Vonnda\Subscription\Model\SubscriptionHistory::SUBSCRIPTION_CUSTOMER_ID,
                $setup->getTable('vonnda_subscription_customer'),
                'id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $setup->getFkName('vonnda_subscription_history',
                                  \Vonnda\Subscription\Model\SubscriptionHistory::CUSTOMER_ID, 
                                  'customer_entity', 
                                  'entity_id'),
                \Vonnda\Subscription\Model\SubscriptionHistory::CUSTOMER_ID,
                $setup->getTable('customer_entity'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
            )
            ->addForeignKey(
                $setup->getFkName('vonnda_subscription_history',
                                  \Vonnda\Subscription\Model\SubscriptionHistory::ADMIN_USER_ID, 
                                  'admin_user', 
                                  'user_id'),
                \Vonnda\Subscription\Model\SubscriptionHistory::ADMIN_USER_ID,
                $setup->getTable('admin_user'),
                'user_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
            )
            ->setComment('Subscription History');
        $setup->getConnection()->createTable($table);

        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function addErrorMessageToSubscriptionCustomer(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->addColumn(
            $setup->getTable('vonnda_subscription_customer'),
            \Vonnda\Subscription\Model\SubscriptionCustomer::ERROR_MESSAGE,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'default' =>null,
                'comment' => 'Error Message'
            ]
        );
        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function dropEmailColumnFromSubscriptionCustomer(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->dropColumn($setup->getTable('vonnda_subscription_customer'), 'email');
        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function renamePromoCodeToCouponCode(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->changeColumn(
            $setup->getTable('vonnda_subscription_promo'),
            'promo_code',
            \Vonnda\Subscription\Model\SubscriptionPromo::COUPON_CODE,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => false,
                'length' => 255,
                'comment' => 'Coupon Code'
            ]
        );
        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function renameDefaultPromoCodesToPromoIds(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->changeColumn(
            $setup->getTable('vonnda_subscription_plan'),
            'default_promo_codes',
            \Vonnda\Subscription\Model\SubscriptionPlan::DEFAULT_PROMO_IDS,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Default Promo Ids'
            ]
        );
        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function addDeviceManagerIdToSubscriptionCustomer(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->addColumn(
            $setup->getTable('vonnda_subscription_customer'),
            \Vonnda\Subscription\Model\SubscriptionCustomer::DEVICE_ID,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'nullable' => true,
                'unsigned' => true,
                'default' => null,
                'comment' => 'Device Id'
            ]);
            $setup->getConnection()->addForeignKey(
                $setup->getFkName('vonnda_subscription_customer',
                                \Vonnda\Subscription\Model\SubscriptionCustomer::DEVICE_ID, 
                                'vonnda_devicemanagment_device', 
                                'entity_id'),
                $setup->getTable('vonnda_subscription_customer'),
                \Vonnda\Subscription\Model\SubscriptionCustomer::DEVICE_ID,
                $setup->getTable('vonnda_devicemanagment_device'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
            );
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function changeDurationColumnOnSubscriptionPlan(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->changeColumn(
            $setup->getTable('vonnda_subscription_plan'),
            'duration',
            \Vonnda\Subscription\Model\SubscriptionPlan::DURATION,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'nullable' => true,
                'default' => null,
                'comment' => 'Duration'
            ],
            true
        );
        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function addNumberOfFreeShipmentsToSubscriptionPlan(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->addColumn(
            $setup->getTable('vonnda_subscription_plan'),
            \Vonnda\Subscription\Model\SubscriptionPlan::NUMBER_OF_FREE_SHIPMENTS,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'nullable' => true,
                'default' =>null,
                'comment' => 'Number of Free Shipments'
            ]
        );
        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function addPriceOverrideToSubscriptionProduct(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->addColumn(
            $setup->getTable('vonnda_subscription_product'),
            \Vonnda\Subscription\Model\SubscriptionProduct::PRICE_OVERRIDE,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'nullable' => true,
                'default' =>null,
                'length' => '12,4',
                'comment' => 'Price Override'
            ]
        );
        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function addStateToSubscriptionCustomer(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->addColumn(
            $setup->getTable('vonnda_subscription_customer'),
            \Vonnda\Subscription\Model\SubscriptionCustomer::STATE,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => false,
                'length' => 255,
                'default' => \Vonnda\Subscription\Model\SubscriptionCustomer::ACTIVE_STATE,
                'comment' => 'State'
            ]
        );
        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function dropSubscriptionCustomerIdFromSubscriptionPayment(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->dropColumn($setup->getTable('vonnda_subscription_payment'), 'subscription_customer_id');
        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function addSubscriptionPaymentIdToSubscriptionCustomer(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->addColumn(
            $setup->getTable('vonnda_subscription_customer'),
            \Vonnda\Subscription\Model\SubscriptionCustomer::SUBSCRIPTION_PAYMENT_ID,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'nullable' => true,
                'unsigned' => true,
                'default' => null,
                'comment' => 'Subscription Payment Id'
            ]
        );
        $setup->getConnection()->addForeignKey(
            $setup->getFkName('vonnda_subscription_customer',
                            \Vonnda\Subscription\Model\SubscriptionCustomer::SUBSCRIPTION_PAYMENT_ID, 
                            'vonnda_subscription_payment', 
                            'id'),
            $setup->getTable('vonnda_subscription_customer'),
            \Vonnda\Subscription\Model\SubscriptionCustomer::SUBSCRIPTION_PAYMENT_ID,
            $setup->getTable('vonnda_subscription_payment'),
            'id',
            \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
        );
        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function dropBillingAddressIdFromSubscriptionPayment(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->dropColumn($setup->getTable('vonnda_subscription_payment'), 'billing_address_id');
        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function addBillingAddressToSubscriptionPayment(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->addColumn(
            $setup->getTable('vonnda_subscription_payment'),
            \Vonnda\Subscription\Model\SubscriptionPayment::BILLING_ADDRESS,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'default' => null,
                'comment' => 'Billing Address'
            ]
        );
        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function addParentOrderIdToSubscriptionCustomer(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->addColumn(
            $setup->getTable('vonnda_subscription_customer'),
            \Vonnda\Subscription\Model\SubscriptionCustomer::PARENT_ORDER_ID,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'nullable' => true,
                'unsigned' => true,
                'default' => null,
                'comment' => 'Parent Order Id'
            ]
        );
        $setup->getConnection()->addForeignKey(
            $setup->getFkName('vonnda_subscription_customer',
                            \Vonnda\Subscription\Model\SubscriptionCustomer::PARENT_ORDER_ID, 
                            'sales_order', 
                            'entity_id'),
            $setup->getTable('vonnda_subscription_customer'),
            \Vonnda\Subscription\Model\SubscriptionCustomer::PARENT_ORDER_ID,
            $setup->getTable('sales_order'),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
        );
        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function addDeviceSkuToSubscriptionPlan(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->addColumn(
            $setup->getTable('vonnda_subscription_plan'),
            \Vonnda\Subscription\Model\SubscriptionPlan::DEVICE_SKU,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'default' => null,
                'comment' => 'Device Sku'
            ]
        );
        $setup->endSetup();
    }

    protected function addCancelReasonToSubscriptionCustomer(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->addColumn(
            $setup->getTable('vonnda_subscription_customer'),
            \Vonnda\Subscription\Model\SubscriptionCustomer::CANCEL_REASON,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'default' => null,
                'comment' => 'Cancel Reason'
            ]
        );
        $setup->endSetup();
    }

    protected function updatePlansForLegacySupport(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->addColumn(
            $setup->getTable('vonnda_subscription_plan'),
            \Vonnda\Subscription\Model\SubscriptionPlan::PAYMENT_REQUIRED_FOR_FREE,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                'nullable' => true,
                'default' => null,
                'comment' => 'Require Payment to Recive Free Shipments'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('vonnda_subscription_customer'),
            \Vonnda\Subscription\Model\SubscriptionCustomer::END_DATE,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                'nullable' => true,
                'default' => null,
                'comment' => 'Subscription Expires on this Day'
            ]
        );
        $setup->endSetup();
    }

    protected function addIdentifierToPlans(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->addColumn(
            $setup->getTable('vonnda_subscription_plan'),
            \Vonnda\Subscription\Model\SubscriptionPlan::IDENTIFIER,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'default' => null,
                'comment' => 'Unique Identifier used in code'
            ]
        );
        $setup->endSetup();
    }

    protected function addShippingMethodAndCostToSubscriptionCustomer(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->addColumn(
            $setup->getTable('vonnda_subscription_customer'),
            \Vonnda\Subscription\Model\SubscriptionCustomer::SHIPPING_METHOD_OVERWRITE,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'default' => null,
                'comment' => 'Shipping method overwrite for next auto-refill order'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('vonnda_subscription_customer'),
            \Vonnda\Subscription\Model\SubscriptionCustomer::SHIPPING_COST_OVERWRITE,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'nullable' => true,
                'default' =>null,
                'length' => '12,4',
                'comment' => 'Shipping cost overwrite for next auto-refill order'
            ]
        );
        $setup->endSetup();
    }

    protected function addStoreIdToSubscriptionPlan(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->addColumn(
            $setup->getTable('vonnda_subscription_plan'),
            \Vonnda\Subscription\Model\SubscriptionPlan::STORE_ID,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'nullable' => true,
                'unsigned' => true,
                'default' => null,
                'comment' => 'Store Id'
            ]
        );
        $setup->getConnection()->addForeignKey(
            $setup->getFkName('vonnda_subscription_plan',
                            \Vonnda\Subscription\Model\SubscriptionPlan::STORE_ID, 
                            'store', 
                            'store_id'),
            $setup->getTable('vonnda_subscription_plan'),
            \Vonnda\Subscription\Model\SubscriptionPlan::STORE_ID,
            $setup->getTable('store'),
            'store_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
        );
        $setup->endSetup();
    }

    protected function addFallbackPlanToSubscriptionPlan(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->addColumn(
            $setup->getTable('vonnda_subscription_plan'),
            \Vonnda\Subscription\Model\SubscriptionPlan::FALLBACK_PLAN,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'default' => null,
                'comment' => 'Fallback plan'
            ]
        );
        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function changeDefaultUpdatedAtForSubModels(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->changeColumn(
            $setup->getTable('vonnda_subscription_customer'),
            'updated_at',
            \Vonnda\Subscription\Model\SubscriptionCustomer::UPDATED_AT,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE,
                'nullable' => false,
                'comment' => 'Updated At'
            ]
        );
        $setup->getConnection()->changeColumn(
            $setup->getTable('vonnda_subscription_payment'),
            'updated_at',
            \Vonnda\Subscription\Model\SubscriptionPayment::UPDATED_AT,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE,
                'nullable' => false,
                'comment' => 'Updated At'
            ]
        );
        $setup->getConnection()->changeColumn(
            $setup->getTable('vonnda_subscription_order'),
            'updated_at',
            \Vonnda\Subscription\Model\SubscriptionOrder::UPDATED_AT,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE,
                'nullable' => false,
                'comment' => 'Updated At'
            ]
        );
        $setup->getConnection()->changeColumn(
            $setup->getTable('vonnda_subscription_plan'),
            'updated_at',
            \Vonnda\Subscription\Model\SubscriptionPlan::UPDATED_AT,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE,
                'nullable' => false,
                'comment' => 'Updated At'
            ]
        );
        $setup->endSetup();
    }

    /** @param SchemaSetupInterface $setup */
    public function updateSubscriptionPaymentTable(SchemaSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()
            ->dropForeignKey(
                'vonnda_subscription_payment',
                $setup->getFkName(
                    'vonnda_subscription_payment',
                    \Vonnda\Subscription\Model\SubscriptionPayment::STRIPE_CUSTOMER_ID,
                    'cryozonic_stripe_customers',
                    'id'
                )
            );
        $setup->getConnection()
            ->addForeignKey(
                $setup->getFkName(
                    'vonnda_subscription_payment',
                    \Vonnda\Subscription\Model\SubscriptionPayment::STRIPE_CUSTOMER_ID,
                    'stripe_customers',
                    'id'
                ),
                'vonnda_subscription_payment',
                \Vonnda\Subscription\Model\SubscriptionPayment::STRIPE_CUSTOMER_ID,
                'stripe_customers',
                'id',
                \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
            );
        $setup->endSetup();
    }

}
