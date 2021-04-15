<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Setup;

use Vonnda\Subscription\Api\SubscriptionPlanRepositoryInterface;
use Vonnda\DeviceManager\Api\DeviceManagerRepositoryInterface as SubscriptionDeviceRepositoryInterface;
use Vonnda\Subscription\Api\SubscriptionCustomerRepositoryInterface;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\State as AppState;
use Magento\Framework\App\Area;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Upgrade data
 */
class UpgradeData implements UpgradeDataInterface
{
    const STORE_CODE_US = "mlk_us_sv";

    const STORE_CODE_CA = "mlk_ca_sv";

    const CA_SUBSCRIPTION_PLAN_IDENTIFIER = "mh1-basic-ca";
    
    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    protected $eavSetupFactory;

    /**
     * Subscription Plan Respository
     *
     * @var SubscriptionPlanRepositoryInterface
     */
    protected $subscriptionPlanRepository;

    /**
     * Subscription Device Respository
     *
     * @var SubscriptionDeviceRepositoryInterface
     */
    protected $subscriptionDeviceRepository;

    /**
     * Subscription Customer Respository
     *
     * @var SubscriptionCustomerRepositoryInterface
     */
    protected $subscriptionCustomerRepository;

    /**
     * Customer Respository
     *
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * Search Criteria Builder
     *
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * App State
     *
     * @var AppState
     */
    protected $appState;

    /**
     * Store Manager
     *
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Resource Connecetion
     *
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Init
     *
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
            EavSetupFactory $eavSetupFactory,
            SubscriptionPlanRepositoryInterface $subscriptionPlanRepository,
            CustomerRepositoryInterface $customerRepository,
            SearchCriteriaBuilder $searchCriteriaBuilder,
            AppState $appState,
            StoreManagerInterface $storeManager,
            ResourceConnection $resourceConnection,
            SubscriptionDeviceRepositoryInterface $subscriptionDeviceRepository,
            SubscriptionCustomerRepositoryInterface $subscriptionCustomerRepository,
            LoggerInterface $logger
    )
    {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->subscriptionPlanRepository = $subscriptionPlanRepository;
        $this->subscriptionDeviceRepository = $subscriptionDeviceRepository;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->customerRepository = $customerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->appState = $appState;
        $this->storeManager = $storeManager;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '2.1.0', '<')) {
            $this->addSubscriptionPlanSkuAndFlagToProduct($setup);
        }

        if (version_compare($context->getVersion(), '2.14.0', '<')) {
            $this->appState->setAreaCode(Area::AREA_ADMINHTML);
            $this->setStoreIdOnSubscriptionPlan($setup);
        }

        if (version_compare($context->getVersion(), '2.16.0', '<')) {
            $this->appState->setAreaCode(Area::AREA_ADMINHTML);
            $this->setLegacyFallbackPlans();
        }

        if (version_compare($context->getVersion(), '2.17.0', '<')) {
            $this->fixUpdatedAtValuesOnSubscriptions();
        }

        if (version_compare($context->getVersion(), '2.19.0', '<')) {
            $this->appState->setAreaCode(Area::AREA_ADMINHTML);
            $this->fixNullDeviceSkuOnDeviceTable();
        }

        $setup->endSetup();
    }

    public function addSubscriptionPlanSkuAndFlagToProduct(ModuleDataSetupInterface $setup)
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'vonnda_subscription_associated_trigger_sku',
                [
                        'type' => 'varchar',
                        'backend' => '',
                        'frontend' => '',
                        'label' => 'Associated Subscription Plan Trigger Sku',
                        'input' => 'text',
                        'class' => '',
                        'source' => '',
                        'global' => \Magento\Catalog\Model\ResourceModel\Eav\Attribute::SCOPE_WEBSITE,
                        'group' => 'General',
                        'visible' => true,
                        'required' => false,
                        'user_defined' => false,
                        'searchable' => false,
                        'filterable' => false,
                        'comparable' => false,
                        'visible_on_front' => false,
                        'used_in_product_listing' => false,
                        'unique' => false,
                        'apply_to' => '',
                ]
        );

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'vonnda_subscription_device_flag',
            [
                    'type' => 'int',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Subscription Device Flag',
                    'input' => 'boolean',
                    'default' => false,
                    'class' => '',
                    'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                    'global' => \Magento\Catalog\Model\ResourceModel\Eav\Attribute::SCOPE_WEBSITE,
                    'group' => 'General',
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => false,
                    'unique' => false,
                    'apply_to' => '',
            ]
        );
    }

    public function setStoreIdOnSubscriptionPlan(ModuleDataSetupInterface $setup)
    {
        $stores = $this->getStoreCodeToIdMap();
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $subscriptionPlans = $this->subscriptionPlanRepository->getList($searchCriteria);
        foreach($subscriptionPlans->getItems() as $subscriptionPlan){
            if($subscriptionPlan->getIdentifier() === self::CA_SUBSCRIPTION_PLAN_IDENTIFIER){
                $subscriptionPlan->setStoreId($stores[self::STORE_CODE_CA]);
            } else {
                $subscriptionPlan->setStoreId($stores[self::STORE_CODE_US]);
            }
            $this->subscriptionPlanRepository->save($subscriptionPlan);
        }       
    }

    public function getStoreCodeToIdMap()
    {
        $stores = $this->storeManager->getStores();
        $storeMap = [];
        foreach($stores as $store){
            $storeMap[$store->getCode()] = $store->getStoreId();
        }

        return $storeMap;
    }

    public function setLegacyFallbackPlans()
    {
        $this->updateFallbackPlan('mh1-sub-legacy-6450-payment-required', 'mh1-sub-legacy-6450');
        $this->updateFallbackPlan('mh1-sub-legacy-6500-payment-required', 'mh1-sub-legacy-6500');
    }

    public function updateFallbackPlan($identifier, $fallbackIdentifier)
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $subscriptionPlans = $this->subscriptionPlanRepository->getList($searchCriteria);
        foreach($subscriptionPlans->getItems() as $subscriptionPlan){
            if($subscriptionPlan->getIdentifier() === $identifier){
                $subscriptionPlan->setFallbackPlan($fallbackIdentifier);
                $this->subscriptionPlanRepository->save($subscriptionPlan);
            }
        }   
    }

    public function fixUpdatedAtValuesOnSubscriptions()
    {
        $tables = [
            $this->resourceConnection->getTableName("vonnda_subscription_customer"),
            $this->resourceConnection->getTableName("vonnda_subscription_order"),
            $this->resourceConnection->getTableName("vonnda_subscription_plan"),
            $this->resourceConnection->getTableName("vonnda_subscription_payment")
        ];

        foreach($tables as $table){
            $this->fixUpdatedAtOnTable($table);
        }
    }

    public function fixUpdatedAtOnTable($tableName)
    {
        $query = "
            UPDATE {$tableName} main
            SET main.updated_at = main.created_at
            WHERE main.updated_at IS NULL;
        ";

        $connection = $this->resourceConnection->getConnection();
        $connection->exec($query);
    }

    public function fixNullDeviceSkuOnDeviceTable()
    {
        $this->logger->info("Update null device sku script starting... ");
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('sku',"",'eq')
            ->create();
        
        $devices = $this->subscriptionDeviceRepository->getList($searchCriteria)->getItems();
        $deviceCount = count($devices);
        $devicesUpdated = 0;
        foreach($devices as $device){
            try {
                $searchCriteria = $this->searchCriteriaBuilder
                    ->addFilter('device_id',$device->getEntityId(),'eq')
                    ->create();
            
                $subscriptionCustomers = $this->subscriptionCustomerRepository->getList($searchCriteria);
                if(count($subscriptionCustomers->getItems()) == 0){
                    throw new \Exception('Corresponding subscription customer for device id: ' . $device->getEntityId() . ' not found.');
                }
                foreach($subscriptionCustomers->getItems() as $subscriptionCustomer){ //Should only be one
                    $subscriptionPlan = $subscriptionCustomer->getSubscriptionPlan();
                    if(!$subscriptionPlan->getDeviceSku()){
                        throw new \Exception('Corresponding subscription plan: ' . $subscriptionPlan->getTitle() . ' has a null device sku.');
                    }
                    $device->setSku($subscriptionPlan->getDeviceSku());
                    $this->subscriptionDeviceRepository->save($device);
                    $devicesUpdated++;
                }
            } catch (\Error $e){
                $this->logger->critical($e->getMessage());
            } catch (\Exception $e){
                $this->logger->info($e->getMessage());
            }

            if(($devicesUpdated % 1000) === 0){
                $this->logger->info($devicesUpdated . " devices updated.");
            }
        }

        $this->logger->info("Update null device sku script run successfully");
        $summaryMessage = $devicesUpdated . "/" . $deviceCount . " successfully updated";
        $this->logger->info($summaryMessage);
    }

}