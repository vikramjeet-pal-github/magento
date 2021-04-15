<?php

/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Controller\Customer;

use Vonnda\Subscription\Helper\Logger as SubscriptionLogger;
use Vonnda\Subscription\Api\SubscriptionCustomerRepositoryInterface;
use Vonnda\DeviceManager\Api\DeviceManagerRepositoryInterface as SubscriptionDeviceRepository;
use Vonnda\Netsuite\Model\Client as NetsuiteClient;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;


class CheckSerialNumber extends Action
{

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * Subscription Logger Helper
     *
     * @var \Vonnda\Subscription\Helper\Logger $subscriptionLogger
     */
    protected $subscriptionLogger;

    /**
     * Subscription Logger Helper
     *
     * @var \Magento\Customer\Model\Session $customerSession
     */
    protected $customerSession;

    /**
     * Subscription Device Repository
     *
     * @var  SubscriptionDeviceRepository $subscriptionDeviceRepository
     */
    protected $subscriptionDeviceRepository;

    /**
     * Subscription Customer Repository
     *
     * @var SubscriptionCustomerRepositoryInterface $subscriptionCustomerRepository
     */
    protected $subscriptionCustomerRepository;

    /**
     * Subscription Logger Helper
     *
     * @var SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Scope Config
     *
     * @var ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;

    /**
     * Store Manager
     *
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * Netsuite Client
     *
     * @var NetsuiteClient $netsuiteClient
     */
    protected $netsuiteClient;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        CustomerSession $customerSession,
        JsonFactory $resultJsonFactory,
        SubscriptionLogger $subscriptionLogger,
        SubscriptionDeviceRepository $subscriptionDeviceRepository,
        SubscriptionCustomerRepositoryInterface $subscriptionCustomerRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        NetsuiteClient $netsuiteClient
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->subscriptionLogger = $subscriptionLogger;
        $this->subscriptionDeviceRepository = $subscriptionDeviceRepository;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->netsuiteClient = $netsuiteClient;

        parent::__construct($context);
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        return $this->checkSerialNumber($params);
    }

    public function getConfigValue($path, $storeId = null)
    {
        if(!$storeId){
            $storeId = $this->storeManager->getStore()->getId();
        }
        
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function checkSerialNumber($params)
    {
        try {
            $result = $this->resultJsonFactory->create();
            // if valid serial number IS NOT required, no need to run the check, so just return valid
            if ($this->getConfigValue('vonnda_subscriptions_general/serial_number/landing_page_require_valid') == 0) {
                $result->setData([
                    'status' => 'success',
                    'serial_number' => $params['serial_number'],
                    'sales_channel' => null
                ]);
                return $result;
            }

            $netsuiteResult = $this->verifySerialOnNetsuite($params['serial_number']);
            $giftOrder = false;
            if($netsuiteResult === null){
                $result->setData([
                    'status' => 'error',
                    'serial_number' => $params['serial_number'],
                    'message' => "Please enter a valid serial number."//TODO - what would be the failure message?
                ]);
                return $result;
            } elseif(is_array($netsuiteResult)){
                $giftOrder = true;
            }

            if (!isset($params['serial_number']) && $params['serial_number']) {
                throw new \Exception('Invalid Request - serial_number must be valid.');
            }

            if(!$giftOrder){
                $result = $this->verifyDeviceSerialInMagento($params, $netsuiteResult, $result);
            } else {
                $result = $this->verifyDeviceSerialInMagento($params, null, $result, $giftOrder);
            }

            return $result;
        } catch (\Error $e) {
            $this->subscriptionLogger->info($e->getMessage());
            $result->setData(['status' => 'error', 'exception_message' => $e->getMessage()]);
            return $result;
        } catch (\Exception $e) {
            $this->subscriptionLogger->info($e->getMessage());
            $result->setData(['status' => 'error', 'exception_message' => $e->getMessage()]);
            return $result;
        }
    }

    public function verifySerialOnNetsuite($serialNumber)
    {        
        try {
            $response = $this->netsuiteClient->verifySerialOnNetsuite($serialNumber);
            
            return $response;
        } catch (\Error $e) {
            $this->subscriptionLogger->info("There was a serious error verifying serial number on netsuite.");
            $this->subscriptionLogger->info($e->getMessage());
            return null;
        } catch (\Exception $e) {
            $this->subscriptionLogger->info("There was an exception verifying serial number on netsuite.");
            $this->subscriptionLogger->info($e->getMessage());
            return null;
        }
    }

    public function verifyDeviceSerialInMagento($params, $salesChannel, $result, $giftOrder = false)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('serial_number', $params['serial_number'], 'eq')
            ->create();
        $subscriptionDevices = $this->subscriptionDeviceRepository
            ->getList($searchCriteria)
            ->getItems();
        if (!$subscriptionDevices) {
            $result->setData([
                'status' => 'success', 
                'serial_number' => $params['serial_number'],
                'sales_channel' => $salesChannel,
                'gift_order' => $giftOrder
            ]);
        } else {
            $result->setData([
                'status' => 'error',
                'serial_number' => $params['serial_number'],
                'message' => "This device is already registered.",
                'gift_order' => $giftOrder
            ]);
        }

        return $result;
    }
}
