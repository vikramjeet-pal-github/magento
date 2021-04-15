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

class CustomerLogin implements ObserverInterface
{
    protected $logger;
    protected $_api;
    protected $scopeConfig;
    protected $_leadMeging;
    protected $jsonHelper;
    const XML_PATH_LEAD_SYNC_LOGIN = 'grazitti_maginate/general/maginate_lead_sync_on_login';

    /**
     * @param Logger $logger
     */
    public function __construct(
        Orderapi $Api,
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Grazitti\Maginate\Model\Data $modelData,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->logger = $logger;
        $this->_api = $Api;
        $this->scopeConfig = $scopeConfig;
         $this->jsonHelper = $jsonHelper;
         $this->customerSession = $customerSession;
         $this->modelData = $modelData;
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $this->_leadMeging=$this->scopeConfig->getValue(self::XML_PATH_LEAD_SYNC_LOGIN, $storeScope);
    }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
            
        $customerSession = $this->customerSession;
        if ($this->_leadMeging && !$customerSession->getBanVisitor()):
                $event = $observer->getEvent();
                $customer = $event->getCustomer();
                $customerId = $customer->getId();
                $data['FirstName'] = $customer->getFirstname();
                $data['LastName'] = $customer->getLastname();
                $data['Email'] = $customer->getEmail();
                
                $lead=$this->_api->getleadData($customer->getEmail());
            
                $encodedData = $this->jsonHelper->jsonDecode($lead);
                $item = $this->modelData;
                $item->setCustomerId($customerId);
                $item->setSyncWithMarketo(1);
                $item->save();
            if (isset($encodedData['success']) && isset($encodedData['result'][0]['email'])) {
                if ($encodedData['result'][0]['email']!=$customer->getEmail()) {
                    $data['id'] = $encodedData['result'][0]['id'];
                    $response=$this->_api->leadUpdate($data);
                }
            } else {
                $response = $this->_api->leadIntegration($data);
            }
                    
        endif;
    }
}
