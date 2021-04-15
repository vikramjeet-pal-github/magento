<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */
namespace Vonnda\TealiumTags\Helper;

use Vonnda\TealiumTags\Model\HttpGateway;
use Vonnda\TealiumTags\Helper\Data as DataObjectHelper;
use Vonnda\Subscription\Helper\Logger;

use Carbon\Carbon;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class CreateSubscriptionOrders extends AbstractHelper
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
        DataObjectHelper $dataObjectHelper,
        Context $context
    ) {
        $this->httpGateway = $httpGateway;
        $this->logger = $logger;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context);
    }
    
    public function createAutoRenewalChargeAttemptEvent($quote, $subscriptionCustomer, $attemptNumber)
    {
        $utagData = [];
        $utagData['event_action'] = 'Auto Renewal Charge Attempt';
        $utagData['event_category'] = 'Ecommerce';
        $utagData['tealium_event'] = 'auto_renewal_charge_attempt_api';
        $utagData['activation_location'] = "Account";

        $utagData['session_id'] = "";
        $utagData['customer_id'] = $quote->getCustomerId();
        $utagData['customer_uid'] = $this->dataObjectHelper->getCustomerUid($quote->getCustomerId());
        $utagData['customer_email'] = $quote->getCustomerEmail();
        $utagData['page_type'] = "";
        $utagData['page_url'] = "";
        $utagData['ab_test_group'] = "";
        $utagData['offer_name'] = "";

        $utagData['attempt_number'] = $attemptNumber;

        $utagData = $this->dataObjectHelper->setDeviceFields($utagData, $subscriptionCustomer);

        $utagData = $this->dataObjectHelper->addCartItemsFromQuote($utagData, $quote);

        $utagData = $this->dataObjectHelper->addSiteInfo($utagData);
        
        $success = $this->httpGateway->pushTag($utagData);
        if(!$success){
            $this->logger->info("Failure sending utagData for quote " . $quote->getId() . ", chargeAttemptEvent.");
        }
    }
    
    public function createAutoRenewalChargeSuccessEvent($order, $quote, $subscriptionCustomer, $attemptNumber, $last4)
    {
        $utagData = [];
        $utagData['event_action'] = 'Auto Renewal Charge Success';
        $utagData['event_category'] = 'Ecommerce';
        $utagData['tealium_event'] = 'purchase_api';
        $utagData['event_value'] = number_format($order->getSubtotal(), 2, '.', '');
        $utagData['activation_location'] = "Account";

        $utagData['page_type'] = "";
        $utagData['page_url'] = "";
        $utagData['ab_test_group'] = "";

        $utagData['attempt_number'] = $attemptNumber;

        $utagData = $this->dataObjectHelper->setCustomerFieldsFromOrder($utagData, $order);
        $utagData['email_preferences'] = "";
        $utagData['session_id'] = "";//Not valid in this context

        $utagData = $this->dataObjectHelper->setShippingAndBillingAddressFromOrder($utagData, $order);
        
        $utagData = $this->dataObjectHelper->setOrderFields($utagData, $order, $last4);

        $utagData = $this->dataObjectHelper->addCartItemsFromQuote($utagData, $quote);

        $utagData = $this->dataObjectHelper->addProductInfoFromOrderItems($utagData, $order);

        $utagData = $this->dataObjectHelper->setSubscriptionFields($utagData, $subscriptionCustomer);

        $utagData = $this->dataObjectHelper->addSiteInfo($utagData);

        $utagData = $this->dataObjectHelper->setIsBusinessAddressFromShippingAddress($utagData, $order);

        $success = $this->httpGateway->pushTag($utagData);
        if(!$success){
            $this->logger->info("Failure sending utagData for order " . $order->getId() . ", chargeSuccessEvent.");
        }
    }

    public function createAutoRenewalChargeFailureEvent($quote, $subscriptionCustomer, $attemptNumber, $last4, $errorMessage)
    {
        $utagData = [];
        $utagData['event_action'] = 'Auto Renewal Charge Failed';
        $utagData['event_category'] = 'Ecommerce';
        $utagData['tealium_event'] = 'purchase_failed_api';
        $utagData['activation_location'] = "Account";

        $utagData['session_id'] = "";
        $utagData['customer_id'] = $quote->getCustomerId();
        $utagData['customer_uid'] = $this->dataObjectHelper->getCustomerUid($quote->getCustomerId());
        $utagData['customer_email'] = $quote->getCustomerEmail();
        $utagData['page_type'] = "";
        $utagData['page_url'] = "";
        $utagData['ab_test_group'] = "";
        $utagData['offer_name'] = "";

        $utagData['attempt_number'] = $attemptNumber;
        $utagData['error_message'] = $errorMessage;
        $utagData['event_label'] = $errorMessage;
        $utagData['reason_code'] = $errorMessage;

        $address = $subscriptionCustomer->getShippingAddress();
        $utagData = $this->dataObjectHelper->setShippingAddressFields($utagData, $address);
        
        $utagData = $this->dataObjectHelper->setOrderFieldsFromQuote($utagData, $quote, $last4);

        $utagData = $this->dataObjectHelper->addProductInfoFromQuoteItems($utagData, $quote);

        $utagData = $this->dataObjectHelper->addCartItemsFromQuote($utagData, $quote);

        $utagData = $this->dataObjectHelper->setSubscriptionFields($utagData, $subscriptionCustomer);

        $utagData = $this->dataObjectHelper->setIsBusinessAddressFromShippingAddress($utagData, $address);

        $utagData = $this->dataObjectHelper->addSiteInfo($utagData);

        $success = $this->httpGateway->pushTag($utagData);
        if(!$success){
            $this->logger->info("Failure sending utagData for quote " . $quote->getId() . ", chargeFailureEvent.");
        }
    }

}