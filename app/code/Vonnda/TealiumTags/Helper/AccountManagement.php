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

class AccountManagement extends AbstractHelper
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
    
    public function createForgotPasswordLinkCreatedEvent($email)
    {        
        $utagData = [];
        $utagData['event_action'] = 'Forgot Password - Link Created';
        $utagData['tealium_event'] = 'forgot_password_link_created_api';
        $utagData['customer_email'] = $email;

        $success = $this->httpGateway->pushTag($utagData);
        if(!$success){
            $this->logger->info("Failure sending utagData for create forgot password link event, customer email " . $email);
        }
    }

}