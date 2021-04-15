<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */
namespace Vonnda\TealiumTags\Helper;

use Vonnda\TealiumTags\Model\HttpGateway;
use Vonnda\TealiumTags\Helper\Data as DataObjectHelper;
use Vonnda\Subscription\Helper\Logger;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Customer\Model\Session as CustomerSession;

class EditPost extends AbstractHelper
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
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager,
        DataObjectHelper $dataObjectHelper,
        Context $context
    ) {
        $this->httpGateway = $httpGateway;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context);
    }
    
    public function createEditEmailEvent($oldEmail, $newEmail)
    {
        $customer = $this->customerSession->getCustomer();

        $this->createEditEmailEventByCustomer($customer, $newEmail);
    }

    public function createEditEmailFailureEvent($errorMessage)
    {
        $customer = $this->customerSession->getCustomer();
        
        $this->createEditEmailFailureEventByCustomer($errorMessage, $customer);
    }

    public function createEditEmailEventByCustomer($customer, $newEmail)
    {
        $utagData = [];
        $utagData['event_action'] = 'Change Email Success';
        $utagData['event_category'] = 'Account';
        $utagData['tealium_event'] = 'change_email_success_api';
        $utagData['account_flow'] = 'Account';

        $utagData['session_id'] = $this->customerSession->getSessionId();
        $utagData['customer_id'] = $customer->getId();
        $utagData['customer_uid'] = $this->dataObjectHelper->getCustomerUid($customer->getId());
        $utagData['customer_email'] = $newEmail;
        $utagData['customer_email_previous'] = $customer->getEmail();
        $utagData['page_type'] = "account";
        $utagData['page_url'] = $this->storeManager->getStore()->getBaseUrl() . "customer/account";

        $success = $this->httpGateway->pushTag($utagData);
        if(!$success){
            $this->logger->info("Failure sending utagData for e-mail change event, customer ID: " . $customer->getId());
        }
    }

    public function createEditEmailFailureEventByCustomer($errorMessage, $customer)
    {
        $utagData = [];
        $utagData['event_action'] = 'Change Email Failure';
        $utagData['event_category'] = 'Account';
        $utagData['tealium_event'] = 'change_email_failure';
        $utagData['account_flow'] = 'Account';
        $utagData['event_label'] = $errorMessage;

        $utagData['session_id'] = $this->customerSession->getSessionId();
        $utagData['customer_id'] = $customer->getId();
        $utagData['customer_uid'] = $this->dataObjectHelper->getCustomerUid($customer->getId());
        $utagData['customer_email'] = $customer->getEmail();
        $utagData['page_type'] = "account";
        $utagData['page_url'] = $this->storeManager->getStore()->getBaseUrl() . "customer/account";
        $utagData['error_message'] = $errorMessage;

        $success = $this->httpGateway->pushTag($utagData);
        if(!$success){
            $this->logger->info("Failure sending utagData for e-mail change event, customer ID: " . $customer->getId());
        }
    }

    //Not sure if this was the initial intent
    public function createEditPasswordSuccessEvent()
    {
        $customer = $this->customerSession->getCustomer();
        
        $utagData = [];
        $utagData['event_action'] = 'Change Password Success';
        $utagData['event_category'] = 'Account';
        $utagData['tealium_event'] = 'change_password_success';
        $utagData['account_flow'] = 'Account';

        $utagData['session_id'] = $this->customerSession->getSessionId();
        $utagData['customer_id'] = $customer->getId();
        $utagData['customer_uid'] = $this->dataObjectHelper->getCustomerUid($customer->getId());
        $utagData['customer_email'] = $customer->getEmail();
        $utagData['page_type'] = "account";
        $utagData['page_url'] = $this->storeManager->getStore()->getBaseUrl() . "customer/account";
        $utagData['ab_test_group'] = "";
        $utagData['offer_name'] = "";
        
        $success = $this->httpGateway->pushTag($utagData);
        if(!$success){
            $this->logger->info("Failure sending utagData for e-mail change event, customer ID: " . $customer->getId());
        }
    }

}