<?php
/**
 * Copyright © 2020 Grazitti. All rights reserved.
 */
namespace Grazitti\Maginate\Observer;

use Magento\Customer\Model\Logger;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Grazitti\Maginate\Model\Orderapi;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Customer log observer.
 */
class AfterRegistrationAtObserver implements ObserverInterface
{
    /**
     * Logger of customer's log data.
     *
     * @var Logger
     */
    protected $logger;
    protected $_api;
    protected $scopeConfig;
    protected $_leadIntegration;
    const XML_PATH_LEAD_INTEGRATION = 'grazitti_maginate/general/maginate_lead_integration';

    /**
     * @param Logger $logger
     */
    public function __construct(
        Orderapi $Api,
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Grazitti\Maginate\Helper\Data $dataHelper,
        \Magento\Customer\Model\Customer $customerData,
        \Grazitti\Maginate\Model\Data $modelData,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->logger = $logger;
        $this->_api = $Api;
        $this->scopeConfig = $scopeConfig;
        $this->jsonHelper = $jsonHelper;
        $this->dataHelper = $dataHelper;
        $this->customerData = $customerData;
        $this->modelData = $modelData;
        $this->customerSession = $customerSession;
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $this->_leadIntegration=$this->scopeConfig->getValue(self::XML_PATH_LEAD_INTEGRATION, $storeScope);
    }
    /**
     * Handler for 'customer_login' event.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $customerSession = $this->customerSession;
        if ($this->_leadIntegration && !$customerSession->getBanVisitor()):
                $event = $observer->getEvent();
                $customer = $event->getCustomer();
                $customerId = $customer->getId();
                $item = $this->modelData;
                $item->setCustomerId($customerId);
                $item->setSyncWithMarketo(1);
                $item->save();
                $data['FirstName'] = $customer->getFirstname();
                $data['LastName'] = $customer->getLastname();
                $data['Email'] = $customer->getEmail();
                $lead=$this->_api->getleadData();
                $this->_api->leadIntegration($data);
        endif;
    }
}
