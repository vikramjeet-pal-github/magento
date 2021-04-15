<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */
namespace Vonnda\TealiumTags\Helper;

use Vonnda\Subscription\Model\SubscriptionCustomer;
use Vonnda\TealiumTags\Model\HttpGateway;
use Vonnda\TealiumTags\Helper\Data as DataObjectHelper;
use Vonnda\Subscription\Helper\Logger;

use Carbon\Carbon;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Customer\Model\Session as CustomerSession;

class DeviceRegistration extends AbstractHelper
{
    /**
     * Http Gateway
     *
     * @var \Vonnda\TealiumTags\Model\HttpGateway $customerSession
     */
    protected $httpGateway;

    /**
     * Vonnda Logger
     *
     * @var \Vonnda\Subscription\Helper\Logger $logger
     */
    protected $logger;

    /**
     * Customer Session
     *
     * @var \Magento\Customer\Model\Session $customerSession
     */
    protected $customerSession;

    /**
     * Store Manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * DataObject Helper
     *
     * @var \Vonnda\TealiumTags\Helper\Data $dataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * 
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        HttpGateway $httpGateway,
        Logger $logger,
        Context $context,
        DataObjectHelper $dataObjectHelper,
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager
    ) {
        $this->httpGateway = $httpGateway;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context);
    }

    public function createRegisterDeviceStepOneEvent(
        $retailChannel, 
        $purchaseDate,
        $serialNumber,
        $parentGiftOrder = null
    ){
        $utagData = [];
        $utagData['tealium_event'] = 'register_device_step1_api';
        $utagData['event_category'] = 'Register Device';
        $utagData['event_action'] = 'Clicked Next';
        $utagData['event_label'] = 'device_pool';
        $utagData['retail_channel'] = $retailChannel;
        $utagData['event_platform'] = "Account";
        $utagData['gift_purchase'] = $parentGiftOrder ? true : false;

        $utagData['session_id'] = $this->customerSession->getSessionId() ?
        $this->customerSession->getSessionId() : "";
        
        $purchaseDate = Carbon::createFromFormat('m/d/Y', $purchaseDate)
            ->startOfDay()
            ->toDateTimeString();
        $utagData['event_value'] = $purchaseDate;
        $utagData['order_date'] = $purchaseDate;

        $utagData['serial_number'][] = $serialNumber;
        
        $success = $this->httpGateway->pushTag($utagData);
        if(!$success){
            $this->logger->info("Failure sending utagData for Register Device, register_device_step1_api event failed, serial number " . $serialNumber);
        }
    }

    public function createRegisterDeviceFailEvent($serialNumber, $platform = 'Account', $parentGiftOrder = null)
    {
        $utagData = [];
        $utagData['tealium_event'] = 'register_device_fail_api';
        $utagData['event_category'] = 'Register Device';
        $utagData['event_action'] = 'Entered Serial';
        $utagData['event_label'] = 'error_message';
        $utagData['event_value'] = 'failed_serial_number';
        $utagData['serial_number'][] = $serialNumber;
        $utagData['event_platform'] = $platform;
        $utagData['gift_purchase'] = $parentGiftOrder ? true : false;

        $utagData['session_id'] = $this->customerSession->getSessionId();

        $success = $this->httpGateway->pushTag($utagData);
        if(!$success){
            $this->logger->info("Failure sending utagData for Register Device enter serial, serial number " . $serialNumber);
        }
    }

    public function createRegisterDeviceSubmitEvent(
        $customer, 
        $subscriptionCustomer,
        $purchaseDate,
        $retailChannel,
        $platform = 'Account',
        $parentGiftOrder = null
    ){
        $utagData = [];
        $utagData['tealium_event'] = 'register_device_api';
        $utagData['event_category'] = 'Register Device';
        $utagData['event_action'] = 'Clicked Submit';
        $utagData['event_label'] = 'device_pool';
        $utagData['event_platform'] = $platform;
        $utagData['gift_purchase'] = $parentGiftOrder ? true : false;
        
        $purchaseDate = Carbon::createFromFormat('m/d/Y', $purchaseDate)
            ->startOfDay()
            ->toDateTimeString();
        $utagData['event_value'] = $purchaseDate;
        $utagData['order_date'] = $purchaseDate;
        
        $utagData['retail_channel'] = $retailChannel;
        
        $utagData['session_id'] = $this->customerSession->getSessionId();
        
        $utagData['customer_email'] = $customer->getEmail();
        $utagData['customer_first_name_billing'] = $customer->getFirstname();
        $utagData['customer_first_name_shipping'] = $customer->getFirstname();
        $utagData['customer_id'] = $customer->getId();
        $utagData['customer_uid'] = $this->dataObjectHelper->getCustomerUid($customer->getId());
        $utagData['customer_last_name_billing'] = $customer->getLastname();
        
        $utagData = $this->dataObjectHelper->setSubscriptionFields($utagData, $subscriptionCustomer);
        $utagData = $this->dataObjectHelper->addProductNamesBySubscriptionFields($utagData);
        
        $success = $this->httpGateway->pushTag($utagData);
        if(!$success){
            $this->logger->info("Failure sending utagData for Register Device Api, subscription ID: " . $subscriptionCustomer->getId());
        }
    }
}