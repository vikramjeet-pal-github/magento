<?php

namespace Vonnda\Checkout\Plugin;

use Magento\Sales\Model\Order;

class GetOrderDetails 
{
    public function __construct(Order $orderFactory, \Magento\Checkout\Model\Session $checkoutSession, \Psr\Log\LoggerInterface $logger) {
        $this->logger = $logger;
        $this->order = $orderFactory;
        $this->checkoutSession = $checkoutSession;
    }

    public function aroundGetAdditionalInfoHtml(\Magento\Checkout\Block\Onepage\Success $subject, callable $proceed) {
        $oldReturn = $proceed();
        
        return $this->order->load($this->checkoutSession->getLastOrderId());
    }
}