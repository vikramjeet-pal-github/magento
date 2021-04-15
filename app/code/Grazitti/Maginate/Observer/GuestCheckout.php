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
use Magento\Sales\Api\OrderRepositoryInterface;

class GuestCheckout implements ObserverInterface
{
    protected $logger;
    protected $_api;
    protected $scopeConfig;
    protected $_leadIntegration;
    const ENABLE_ORDER = 'grazitti_maginate/orderconfig/maginate_order_integration';
    
    public function __construct(
        Orderapi $Api,
        Logger $logger,
        OrderRepositoryInterface $OrderRepositoryInterface,
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
        $this->orderRepository = $OrderRepositoryInterface;
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $this->_EnableOrder = $this->scopeConfig->getValue(self::ENABLE_ORDER, $storeScope);
    }
    public function execute(Observer $observer)
    {
        $customerSession = $this->customerSession;
        if ($this->_EnableOrder && !$customerSession->getBanVisitor()):
            try {
                $order = $observer->getEvent()->getOrder();
                $billingAddress = $order->getBillingAddress();
                $customerId = $order->getCustomerId();
                $data['Email'] = $order->getCustomerEmail();
                $data['FirstName'] = $billingAddress->getFirstname();
                $data['LastName'] = $billingAddress->getLastname();
                $item = $this->modelData;
                $item->setCustomerId($customerId);
                $item->setSyncWithMarketo(1);
                $item->save();
                $lead=$this->_api->getleadData();
                $this->_api->leadIntegration($data);
            } catch (\Exception $e) {
                $this->logger->info($e->getMessage());
            }
        endif;
    }
}
