<?php

/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Residential\Setup;

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
use Magento\Customer\Api\AddressRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Upgrade data
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    protected $eavSetupFactory;

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
     * Address Repository
     *
     * @var AddressRepository
     */
    protected $addressRepository;

    /**
     * Init
     *
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        CustomerRepositoryInterface $customerRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AppState $appState,
        StoreManagerInterface $storeManager,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger,
        AddressRepositoryInterface $addressRepository
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->customerRepository = $customerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->appState = $appState;
        $this->storeManager = $storeManager;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
        $this->addressRepository = $addressRepository;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $this->appState->setAreaCode(Area::AREA_ADMINHTML);
            $this->setIsResidentialOnExistingAddresses();
        }

        $setup->endSetup();
    }


    public function setIsResidentialOnExistingAddresses()
    {
        try {
            $this->logger->info("Update is_residential script starting... ");
            $query = "
            SELECT COUNT(*) FROM customer_address_entity
        ";

            $connection = $this->resourceConnection->getConnection();
            $count = $connection->fetchCol($query);
            $this->logger->info($count[0] . " addresses to be updated... ");

            $query = "
            INSERT INTO customer_address_entity_int (
                value_id,
                attribute_id,
                entity_id,
                value
            )
            SELECT 
                null,
                eav.attribute_id,
                address.entity_id,
                1
            FROM 
                customer_address_entity address
            LEFT JOIN
                eav_attribute eav
            ON eav.attribute_code = 'is_residential'
        ";
            $count = $connection->exec($query);

            $this->logger->info("Addresses updated successfully.");
        } catch (\Error $e) {
            $this->logger->info("Addresses not updated successfully.");
            $this->logger->info($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->info("Addresses not updated successfully.");
            $this->logger->info($e->getMessage());
        }
    }
}
