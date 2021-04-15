<?php

namespace Vonnda\TealiumTags\Plugin\Sales\Model;

use Vonnda\TealiumTags\Model\HttpGateway;

use Psr\Log\LoggerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;


class AroundRefundOrder
{
    
    protected $httpGateway;

    protected $logger;

    protected $orderRepository;

    protected $customerRepository;

    public function __construct(
        HttpGateway $httpGateway,
        LoggerInterface $logger,
        OrderRepositoryInterface $orderRepository,
        CustomerRepositoryInterface $customerRepository
    ){
        $this->httpGateway  = $httpGateway;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
    }

    //TODO - This hasn't been tested, and is disabled until further notice
    public function aroundExecute($subject, callable $proceed, $orderId, ...$args){
        $this->logger->info(__METHOD__);
        $this->sendTealiumEvent($orderId);
        
        $result = $proceed($orderId, ...$args);
                
        return $result;
    }

    public function sendTealiumEvent($orderId)
    {
        try {
            $order = $this->orderRepository->getById($orderId);
            $customer = $this->customerRepository->getById($order->getCustomerId());
            $uuid = $customer->getCustomAttribute('cognito_uuid');

            $utagData = [];
            $utagData['event_action'] = 'Refund';
            $utagData['event_category'] = 'Offline Ecommerce';
            $utagData['tealium_event'] = 'return_product_api';

            $utagData['session_id'] = "";
            $utagData['customer_id'] = $customer->getId();
            $utagData['customer_uid'] = $uuid ? $uuid->getValue() : "";
            $utagData['customer_email'] = $customer->getEmail();
            $utagData['page_type'] = "";
            $utagData['page_url'] = "";
            $utagData['ab_test_group'] = "";
            $utagData['offer_name'] = "";

            $address = $order->getShippingAddress();
            $utagData = $this->dataObjectHelper->setShippingAddressFields($utagData, $address);
            
            $utagData = $this->dataObjectHelper->setOrderFields($utagData, $order, "");

            $utagData = $this->dataObjectHelper->addProductInfoFromOrderItems($utagData, $order);

            $utagData = $this->dataObjectHelper->addSiteInfo($utagData);

            $success = $this->httpGateway->pushTag($utagData);
            if(!$success){
                $this->logger->info("Failure sending utagData for shipment " . ", shipOrderEvent.");
            }
        } catch(\Exception $e){
            $this->logger->info($e->getMessage());
        }
    }

}