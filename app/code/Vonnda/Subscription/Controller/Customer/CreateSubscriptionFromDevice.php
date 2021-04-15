<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Controller\Customer;

use Vonnda\Subscription\Helper\Logger as SubscriptionLogger;
use Vonnda\Subscription\Api\SubscriptionCustomerRepositoryInterface;
use Vonnda\DeviceManager\Api\DeviceManagerRepositoryInterface as SubscriptionDeviceRepository;
use Vonnda\Subscription\Model\SubscriptionCustomer;
use Vonnda\Subscription\Model\SubscriptionCustomerFactory;
use Vonnda\Subscription\Model\SubscriptionPayment;
use Vonnda\Subscription\Model\SubscriptionPaymentFactory;
use Vonnda\DeviceManager\Model\DeviceManagerFactory;
use Vonnda\Subscription\Helper\TimeDateHelper;
use Vonnda\Subscription\Model\SubscriptionPlanRepository;
use Vonnda\Cognito\Model\AuthService;
use Vonnda\TealiumTags\Helper\DeviceRegistration as TealiumHelper;
use Vonnda\Netsuite\Model\Client as NetsuiteClient;

use Carbon\Carbon;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;

class CreateSubscriptionFromDevice extends Action
{

    const DEBUG = true;

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
     * Customer Respository
     *
     * @var CustomerRepositoryInterface $customerRepository
     */
    protected $customerRepository;

    /**
     * Customer Factory
     *
     * @var CustomerInterfaceFactory $customerFactory
     */
    protected $customerFactory;

    /**
     * Customer Interface Factory
     *
     * @var CustomerInterfaceFactory $customerInterfaceFactory
     */
    protected $customerInterfaceFactory;

    /**
     * Password Encryption
     *
     * @var Encryptor $encryptor
     */
    protected $encryptor;

    /**
     * Store Manager
     *
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * Subscription Customer Factory
     *
     * @var SubscriptionCustomerFactory $subscriptionCustomerFactory
     */
    protected $subscriptionCustomerFactory;

    /**
     * Subscription Payment Factory
     *
     * @var SubscriptionPaymentFactory $subscriptionPaymentFactory
     */
    protected $subscriptionPaymentFactory;

    /**
     * Subscription Device Factory
     *
     * @var DeviceManagerFactory $subscriptionDeviceFactory
     */
    protected $subscriptionDeviceFactory;

    /**
     * Account Management
     *
     * @var AccountManagementInterface $accountManagement
     */
    protected $accountManagement;

    /**
     * Time Date Helper
     *
     * @var TimeDateHelper $timeDateHelper
     */
    protected $timeDateHelper;

    /**
     * Subscription Plan Repository
     *
     * @var SubscriptionPlanRepository $subscriptionPlanRepository
     */
    protected $subscriptionPlanRepository;

    /**
     * AuthService
     *
     * @var Authservice $authService
     */
    protected $authService;

    /**
     * Tealium Helper
     *
     * @var TealiumHelper $tealiumHelper
     */
    protected $tealiumHelper;

    /**
     * Netsuite Client
     *
     * @var Netsuite $netsuiteClient
     */
    protected $netsuiteClient;

    protected $scopeConfig;

    /**
     * Order Repository
     *
     * @var OrderRepository $orderRepository
     */
    protected $orderRepository;

    /**
     * Shipment Repository
     *
     * @var ShipmentRepository $shipmentRepository
     */
    protected $shipmentRepository;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        CustomerSession $customerSession,
        JsonFactory $resultJsonFactory,
        SubscriptionLogger $subscriptionLogger,
        SubscriptionDeviceRepository $subscriptionDeviceRepository,
        SubscriptionCustomerRepositoryInterface $subscriptionCustomerRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CustomerRepositoryInterface $customerRepository,
        CustomerFactory $customerFactory,
        EncryptorInterface $encryptor,
        StoreManagerInterface $storeManager,
        SubscriptionCustomerFactory $subscriptionCustomerFactory,
        SubscriptionPaymentFactory $subscriptionPaymentFactory,
        DeviceManagerFactory $subscriptionDeviceFactory,
        AccountManagementInterface $accountManagement,
        TimeDateHelper $timeDateHelper,
        SubscriptionPlanRepository $subscriptionPlanRepository,
        AuthService $authService,
        CustomerInterfaceFactory $customerInterfaceFactory,
        TealiumHelper $tealiumHelper,
        NetsuiteClient $netsuiteClient,
        ScopeConfigInterface $scopeConfig,
        OrderRepositoryInterface $orderRepository,
        ShipmentRepositoryInterface $shipmentRepository
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->subscriptionLogger = $subscriptionLogger;
        $this->subscriptionDeviceRepository = $subscriptionDeviceRepository;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->encryptor = $encryptor;
        $this->storeManager = $storeManager;
        $this->subscriptionCustomerFactory = $subscriptionCustomerFactory;
        $this->subscriptionPaymentFactory = $subscriptionPaymentFactory;
        $this->subscriptionDeviceFactory = $subscriptionDeviceFactory;
        $this->accountManagement = $accountManagement;
        $this->timeDateHelper = $timeDateHelper;
        $this->subscriptionPlanRepository = $subscriptionPlanRepository;
        $this->authService = $authService;
        $this->customerInterfaceFactory = $customerInterfaceFactory;
        $this->tealiumHelper = $tealiumHelper;
        $this->netsuiteClient = $netsuiteClient;
        $this->scopeConfig = $scopeConfig;
        $this->orderRepository = $orderRepository;
        $this->shipmentRepository = $shipmentRepository;

        parent::__construct($context);
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $result = $this->resultJsonFactory->create();
		if(isset($params['email'])){
			$params['email'] = strtolower($params['email']);
			\Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)->debug($params['email']);
        }
		try{
            // if valid serial number IS required, check the entered number against netsuite. check is run when SN is entered, but reconfirm here just in case
            if ($this->serialVerificationIsRequired()) {
                $netsuiteResponse = $this->netsuiteClient->verifySerialOnNetsuite($params['serial-number']);
                if(!$netsuiteResponse){
                    throw new \Exception('Please enter a valid serial number.');
                }
                $params = array_merge($params, $netsuiteResponse);
                $giftOrder = $this->fetchGiftOrder($params);

                if($giftOrder){
                    $result = $this->handleGiftDeviceRegistration($params, $result, $giftOrder);
                } else {
                    if (!$params['sales-channel']) {
                        throw new \Exception('Please enter a valid serial number.');
                    }
                    $result = $this->handleDeviceRegistration($params, $result);
                }
            } else {
                $params['sales-channel'] = null;
                $result = $this->handleDeviceRegistration($params, $result);
            }

            return $result;
        } catch(\Error $e) {
            $parentGiftOrder = false;
            if(isset($giftOrder) && $giftOrder){
                $parentGiftOrder = true;
            }
            $this->tealiumHelper->createRegisterDeviceFailEvent($params['serial-number'], $parentGiftOrder);
            $this->subscriptionLogger->critical($e->getMessage());
            $result->setData(['status' => 'error', 'message' => $e->getMessage()]);
            return $result;
        } catch(\Exception $e) {
            $parentGiftOrder = false;
            if(isset($giftOrder) && $giftOrder){
                $parentGiftOrder = true;
            }
            $this->tealiumHelper->createRegisterDeviceFailEvent($params['serial-number'], $parentGiftOrder);
            $this->subscriptionLogger->info($e->getMessage());
            $result->setData(['status' => 'error', 'message' => $e->getMessage()]);
            return $result;
        }
    }

    public function handleDeviceRegistration($params, $result)
    {
        //Logged in customer
        if ($this->customerSession->isLoggedIn()) {
            $customer = $this->customerSession->getCustomer();
            $this->handleLoggedInCustomer($customer, $params, $result);
            return $result;
        }
        //Existing customer in Magento - redirect to login
        $customer = $this->findExistingCustomer($params);
        if ($customer) {
            $this->handleExistingCustomer($customer, $params, $result);
            return $result;
        }
        //The odd case of an existing customer in Cognito, but not Magento
        $cognitoCustomer = $this->getCognitoCustomer($params);
        if ($cognitoCustomer) {
            $this->handleExistingCognitoCustomer($cognitoCustomer, $params, $result);
            return $result;
        }
        //New customer
        $customer = $this->createCustomer($params);
        $this->customerSession->setCustomerDataAsLoggedIn($customer);
        $this->createNewSubscription($customer, $params, $result);
        return $result;
    }

    public function handleGiftDeviceRegistration($params, $result, $giftOrder)
    {
        //Logged in customer
        if ($this->customerSession->isLoggedIn()) {
            $customer = $this->customerSession->getCustomer();
            $this->handleLoggedInCustomer($customer, $params, $result, $giftOrder);
            return $result;
        }

        //Existing customer in Magento - redirect to login
        $customer = $this->findExistingCustomer($params);
        if ($customer) {
            $this->handleExistingCustomer($customer, $params, $result);
            return $result;
        }
        //The odd case of an existing customer in Cognito, but not Magento
        $cognitoCustomer = $this->getCognitoCustomer($params);
        if ($cognitoCustomer) {
            $this->handleExistingCognitoCustomer($cognitoCustomer, $params, $result, $giftOrder);
            return $result;
        }

        //New customer
        $customer = $this->createCustomer($params);
        $this->customerSession->setCustomerDataAsLoggedIn($customer);
        $this->createNewSubscription($customer, $params, $result, $giftOrder);
        return $result;
    }

    public function fetchGiftOrder($params)
    {
        try {
            $order = null;
            //order_id will be the increment id
            if(isset($params['order_id']) && $params['order_id']){
                $order = $this->getOrderFromIncrementId($params['order_id']);
            }

            if(!$order){
                throw new \Exception("Order not found");
            }

            if(!$order->getGiftOrder()){
                throw new \Exception("Order is not marked as gift");
            }
            //Commented out to allow any email to register a gift, not just recipent email from order
            // $shippingAddress = $order->getShippingAddress();
            // if($shippingAddress->getGiftRecipientEmail() != $params['email']){
            //     throw new \Exception("Gift order email doesn't match");
            // }
            return $order;
        } catch(\Exception $e){
            $this->logDebug('Failed to load gift order. ERROR: ' . $e->getMessage());
            return null;
        }
    }

    public function getOrderFromIncrementId($incrementId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('increment_id', $incrementId, 'eq')
            ->create();
        $orderList = $this->orderRepository->getList($searchCriteria);
        foreach($orderList->getItems() as $order){
            return $order;
        }

        throw new \Exception("Order ID: " . $incrementId. " not found");
    }

    public function serialVerificationIsRequired()
    {
        return $this->scopeConfig->getValue(
            'vonnda_subscriptions_general/serial_number/landing_page_require_valid',
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()) == 1;
    }

    public function handleLoggedInCustomer($customer, $params, $result, $parentGiftOrder = null)
    {
        $this->tealiumHelper->createRegisterDeviceStepOneEvent(
            $params['sales-channel'],
            $params['purchase-date'],
            $params['serial-number'],
            $parentGiftOrder);
        $this->createNewSubscription($customer, $params, $result, $parentGiftOrder);
    }

    public function getCognitoCustomer($params)
    {
        $cognitoCustomer = $this->authService->adminGetUser($params['email']);
        if (!$cognitoCustomer || !$cognitoCustomer->get('Enabled')) {
            return null;
        }

        return $cognitoCustomer;
    }

    public function handleExistingCognitoCustomer($cognitoCustomer, $params, $result, $parentGiftOrder = null)
    {
        $userAttributes = $this->extractUserAttributes($cognitoCustomer);
        $uuid = $userAttributes['sub'];
        $hash = $this->encryptor->getHash($uuid.'-mlk2019', true);
        $store = $this->storeManager->getStore();
        $websiteId = $this->storeManager->getStore()->getWebsiteId();

        $customer = $this->customerFactory->create();
        $customer->setWebsiteId($websiteId)
            ->setStore($store)
            ->setFirstname(isset($userAttributes['given_name']) ? $userAttributes['given_name'] : "" )
            ->setLastname(isset($userAttributes['family_name']) ? $userAttributes['family_name'] : "")
            ->setEmail($params['email'])
            ->setPassword($hash);
        $customer->save();

        $customer->getResource()->load($customer, $customer->getId());
        $customer->setData('cognito_uuid', $uuid)
            ->setAttributeSetId(CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER);
        $customer->getResource()->save($customer);

        $this->customerSession->setCustomerDataAsLoggedIn($customer);
        $this->createNewSubscription($customer, $params, $result, $parentGiftOrder);
    }

    protected function extractUserAttributes($cognitoCustomer)
    {
        foreach($cognitoCustomer->get('UserAttributes') as $attribute){
            $attributeMap[$attribute['Name']] = $attribute['Value'];
        }

        return $attributeMap;
    }

    public function createCustomer($params)
    {
        $customer = $this->customerInterfaceFactory->create();
        $store = $this->storeManager->getStore();
        $websiteId = $store->getWebsiteId();

        $customer->setEmail($params['email'])
            ->setWebsiteId($websiteId)
            ->setFirstname($params['firstname'])
            ->setLastname($params['lastname']);

        $customer = $this->accountManagement->createAccount($customer, $params['password']);
        return $customer;
    }

    public function findExistingCustomer($params)
    {
        try {
            $customer = $this->customerRepository->get($params['email']);
            return $customer;
        } catch(\Exception $e){
            return false;
        }

        return false;
    }

    public function handleExistingCustomer($customer, $params, $result)
    {
        $result->setData(['status' => 'error', 'existing_customer' => true, 'message' => 'There is an existing customer with this e-mail, please login.']);
    }

    protected function createNewSubscription($customer, $params, $result, $parentGiftOrder = null)
    {
        if(!isset($params['serial-number']) || !$params['serial-number']){
            throw new \Exception('Invalid serial number.');
        }

        $subscriptionPlan = $this->getSubscriptionPlanFromDeviceSerial($params['serial-number']);
        if(!$subscriptionPlan){
            throw new \Exception('Subscription plan for serial number: ' . $params['serial-number'] . ' not found.');
        }

        $subscriptionCustomer = $this->createSubscriptionCustomer($customer->getId(), $subscriptionPlan, $params, $parentGiftOrder);
        if(!$subscriptionCustomer){
            throw new \Exception('Subscription could not be created.');
        }

        $subscriptionPayment = $this->createSubscriptionPayment($subscriptionCustomer);
        if(!$subscriptionPayment){
            throw new \Exception('Subscription payment could not be created.');
        }

        $subscriptionDevice = $this->createSubscriptionDevice($subscriptionCustomer, $params['serial-number'], $params['sales-channel'], $parentGiftOrder);
        if(!$subscriptionDevice){
            throw new \Exception('Subscription device could not be created.');
        }

        $this->customerSession->setNewDeviceRegistrationId($subscriptionCustomer->getId());
        $this->tealiumHelper->createRegisterDeviceSubmitEvent(
            $customer,
            $subscriptionCustomer,
            $params['purchase-date'],
            $params['sales-channel'],
            'Account',
            $parentGiftOrder);

        $this->sendCustomerInfoToNetsuite($customer, $subscriptionCustomer);
        $result->setData(['status' => 'success', 'subscription_id' => $subscriptionCustomer->getId()]);
        return true;
    }

    protected function createSubscriptionCustomer(
        $customerId,
        $subscriptionPlan,
        $requestParameters,
        $parentGiftOrder = null
    ){
        try {
            $subscriptionPlanId = $subscriptionPlan->getId();

            $frequency = $subscriptionPlan->getFrequency();
            $frequencyUnits = $subscriptionPlan->getFrequencyUnits();

            $startDate = Carbon::createFromFormat('m/d/Y', $requestParameters['purchase-date']);
            $nextOrderDate = $this->timeDateHelper
                ->getNextDateFromFrequencyWithStart($frequency, $frequencyUnits, $startDate);

            $subscriptionCustomer = $this->subscriptionCustomerFactory->create()
                ->setCustomerId($customerId)
                ->setShippingAddressId(null)
                ->setStatus(SubscriptionCustomer::NEW_NO_PAYMENT_STATUS)
                ->setLastOrder(null)
                ->setNextOrder($nextOrderDate)
                ->setSubscriptionPlanId($subscriptionPlanId)
                ->setParentOrderId(null);

            if($parentGiftOrder){
                $subscriptionCustomer
                    ->setParentOrderId($parentGiftOrder->getId())
                    ->setGifted(true);
            }
            $this->subscriptionCustomerRepository->save($subscriptionCustomer);
            $this->logDebug("Subscription ID:" . $subscriptionCustomer->getId() . " created");
            return $subscriptionCustomer;
        } catch(\Exception $e){
            $this->log("Failure creating subscription plan - " . $e->getMessage());
            return false;
        }
    }

    protected function createSubscriptionPayment(
        $subscriptionCustomer
    ){
        $subscriptionPayment = $this->subscriptionPaymentFactory->create()
            ->setPaymentId(null)
            ->setStatus(SubscriptionPayment::INVALID_STATUS);
        $subscriptionCustomer->setStatus(SubscriptionCustomer::NEW_NO_PAYMENT_STATUS)
            ->setPayment($subscriptionPayment);
        $this->subscriptionCustomerRepository->save($subscriptionCustomer);


        $this->logDebug("Subscription payment ID:" . $subscriptionPayment->getId() . " created");
        return $subscriptionPayment;
    }

    protected function parseSerialNumberForSku($serialNumber)
    {
        $skuSection = (explode("-", $serialNumber))[0];
        $skuSection = substr($skuSection, 0, 3);

        $skuMapUS = [
            'MH1' =>  "MH1-BBB-1",
            'MN1' =>  "MN1B-US",
            'MN2' =>  "MN2P-US",
            'SQ2' =>  "SQ2P-US"
        ];

        $skuMapCA = [
            'MH1' => "MH1-BBB-1CA",
            'MN2' => "MN2P-CA"
        ];

        if($this->isCanadaStore()){
            if(!isset($skuMapCA[$skuSection])){
                throw new \Exception("Unable to find device sku.");
            }

            return $skuMapCA[$skuSection];
        }

        if(!isset($skuMapUS[$skuSection])){
            throw new \Exception("Unable to find device sku.");
        }

        return $skuMapUS[$skuSection];
    }

    protected function isCanadaStore()
    {
        $storeCode = $this->storeManager->getStore()->getCode();
        if($storeCode === 'mlk_ca_sv'){
            return true;
        }

        return false;
    }

    protected function getSubscriptionPlanFromDeviceSerial($serialNumber)
    {
        $sku = $this->parseSerialNumberForSku($serialNumber);

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('device_sku', $sku ,'eq')
            ->addFilter('trigger_sku', "", 'neq')
            ->create();

        $subscriptionPlans = $this->subscriptionPlanRepository->getList($searchCriteria);
        foreach($subscriptionPlans->getItems() as $subscriptionPlan){
            return $subscriptionPlan;
        }

        return null;
    }

    protected function createSubscriptionDevice($subscriptionCustomer, $serialNumber, $salesChannel, $parentGiftOrder = null)
    {
        try {
            $sku = $this->parseSerialNumberForSku($serialNumber);
            $subscriptionDevice = $this->subscriptionDeviceFactory->create();
            $subscriptionDevice->setCustomerId($subscriptionCustomer->getCustomerId())
                ->setPurchaseDate(Carbon::now()->toDateTimeString())
                ->setSerialNumber($serialNumber)
                ->setSalesChannel($salesChannel)
                ->setIsSerialNumberValid($salesChannel === null ? 0 : 1)
                ->setSku($sku)
                ->save();

            if($parentGiftOrder){
                $subscriptionDevice->setIsSerialNumberValid(true)->save();
            }

            $subscriptionCustomer->setDeviceId($subscriptionDevice->getEntityId());
            $this->subscriptionCustomerRepository->save($subscriptionCustomer);

            if($subscriptionDevice){
                $this->logDebug("Subscription device ID:" . $subscriptionDevice->getId() . " created, sku not set");
                return $subscriptionDevice;
            } else {
                $this->logDebug("Subscription device not created");
                return null;
            }
        } catch(\Exception $e){
            $this->log("Error associating device on front");
            $this->log($e->getMessage());
            return null;
        }
    }

    protected function sendCustomerInfoToNetsuite($customer, $subscriptionCustomer)
    {
        try {
            $this->netsuiteClient->sendCustomerInfoToNetsuite($customer, $subscriptionCustomer);
        } catch (\Error $e) {
            $this->log("There was a serious error sending customer info to netsuite.");
            $this->log($e->getMessage());
        } catch (\Exception $e) {
            $this->log("There was an exception sending customer info to netsuite.");
            $this->log($e->getMessage());
        }
    }

    public function log($message)
    {
        return $this->subscriptionLogger->logToSubscriptionDebug($message);
    }

    public function logDebug($message)
    {
        if(self::DEBUG){
            return $this->subscriptionLogger->logToSubscriptionDebug($message);
        }
        return;
    }

}
