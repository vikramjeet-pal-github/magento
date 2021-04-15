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

class SubscriptionService extends AbstractHelper
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

    public function createTurnOffAutoRenewEvent($customer, $subscriptionCustomer, $url = null)
    {
        $utagData = [];
        $utagData['event_action'] = 'Turned off Auto-Renew';
        $utagData['event_category'] = 'Account';
        $utagData['tealium_event'] = 'auto_renew_off_api';
        $utagData['event_label'] = $subscriptionCustomer->getCancelReason();
        $utagData['reason_code'] = $subscriptionCustomer->getCancelReason();
        $utagData['activation_location'] = "Account";

        $utagData['session_id'] = $this->customerSession->getSessionId();
        $utagData['customer_id'] = $customer->getId();
        $utagData['customer_uid'] = $this->dataObjectHelper->getCustomerUid($customer->getId());
        $utagData['customer_email'] = $customer->getEmail();
        $utagData['page_type'] = "account";
        
        if(!$url){
            $utagData['page_url'] = $this->storeManager->getStore()->getBaseUrl() . "rest/V1/vonnda/subscription/me/customer";
        } else {
            $utagData['page_url'] = $url;
        }

        $utagData['ab_test_group'] = "";
        $utagData['offer_name'] = "";

        $address = $subscriptionCustomer->getShippingAddress();
        $utagData = $this->dataObjectHelper->setShippingAddressFields($utagData, $address);

        $utagData = $this->dataObjectHelper->setSubscriptionFields($utagData, $subscriptionCustomer);

        $utagData = $this->dataObjectHelper->addSiteInfo($utagData);
        
        $success = $this->httpGateway->pushTag($utagData);
        if(!$success){
            $this->logger->info("Failure sending utagData for Turn Off Auto-Renew Event, subscription ID: " . $subscriptionCustomer->getId());
        }
    }

    public function createActivateAutoRenewEvent($customer, $subscriptionCustomer, $url = null)
    {
        $utagData = [];
        $utagData['event_action'] = 'Activated Auto Refills';
        $utagData['event_category'] = 'Ecommerce';
        $utagData['tealium_event'] = 'activate_auto_refills_success_api';
        $utagData['activation_location'] = "Account";
        $utagData['event_platform'] = $this->dataObjectHelper->getEventPlatformFromAuth();

        $utagData['session_id'] = $this->customerSession->getSessionId();
        $utagData['customer_id'] = $customer->getId();
        $utagData['customer_uid'] = $this->dataObjectHelper->getCustomerUid($customer->getId());
        $utagData['customer_email'] = $customer->getEmail();
        $utagData['page_type'] = "account";
        
        if(!$url){
            $utagData['page_url'] = $this->storeManager->getStore()->getBaseUrl() . "rest/V1/vonnda/subscription/me/customer";
        } else {
            $utagData['page_url'] = $url;
        }
        
        $utagData['ab_test_group'] = "";
        $utagData['offer_name'] = "";
        $utagData['activate_location'] = "";//TODO - pass in as parameter?

        $address = $subscriptionCustomer->getShippingAddress();
        $utagData = $this->dataObjectHelper->setShippingAddressFields($utagData, $address);

        $utagData = $this->dataObjectHelper->setSubscriptionFields($utagData, $subscriptionCustomer);

        $utagData = $this->dataObjectHelper->addSiteInfo($utagData);
        
        $success = $this->httpGateway->pushTag($utagData);
        if(!$success){
            $this->logger->info("Failure sending utagData for Turn Off Auto-Renew Event, subscription ID: " . $subscriptionCustomer->getId());
        }
    }

    public function createAffirmFlowActivateAutoRenewEvent($customer, $subscriptionCustomer, $url = null)
    {
        $utagData = [];
        $utagData['event_action'] = 'Activated Auto Refills';
        $utagData['event_category'] = 'Ecommerce';
        $utagData['tealium_event'] = 'activate_auto_refills_success_api';
        $utagData['activation_location'] = "Affirm Page";
        $utagData['event_platform'] = 'affirm page';

        $utagData['session_id'] = '';
        $utagData['customer_id'] = $customer->getId();
        $utagData['customer_uid'] = $this->dataObjectHelper->getCustomerUid($customer->getId());
        $utagData['customer_email'] = $customer->getEmail();
        $utagData['page_type'] = "affirm_page";

        $utagData['page_url'] = $this->storeManager->getStore()->getBaseUrl() . "checkout/onepage/success/";

        $utagData['ab_test_group'] = "";
        $utagData['offer_name'] = "";
        $utagData['activate_location'] = "";//TODO - pass in as parameter?

        $address = $subscriptionCustomer->getShippingAddress();
        $utagData = $this->dataObjectHelper->setShippingAddressFields($utagData, $address);

        $utagData = $this->dataObjectHelper->setSubscriptionFields($utagData, $subscriptionCustomer);

        $utagData = $this->dataObjectHelper->addSiteInfo($utagData);

        $success = $this->httpGateway->pushTag($utagData);
        if(!$success){
            $this->logger->info("Failure sending utagData for Turn Off Auto-Renew Event, subscription ID: " . $subscriptionCustomer->getId());
        }
    }
}