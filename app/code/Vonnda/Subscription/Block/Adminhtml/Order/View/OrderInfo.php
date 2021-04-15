<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Block\Adminhtml\Order\View;

use Vonnda\Subscription\Model\SubscriptionCustomer;
use Vonnda\Subscription\Model\SubscriptionCustomerRepository;
use Vonnda\Subscription\Model\SubscriptionPlanRepository;
use Vonnda\DeviceManager\Api\DeviceManagerRepositoryInterface;
use Vonnda\Subscription\Helper\Logger;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;


class OrderInfo extends Template
{
    /**
     * Subscription Customer Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionCustomerRepository $subscriptionCustomerRepository
     */
    protected $subscriptionCustomerRepository;

    /**
     * Subscription Plan Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionPlanRepository $subscriptionPlanRepository
     */
    protected $subscriptionPlanRepository;

    /**
     * Device Manager Repository
     *
     * @var \Vonnda\DeviceManager\Api\DeviceManagerRepositoryInterface $subscriptionDeviceRepository
     */
    protected $subscriptionDeviceRepository;
    
    /**
     * Customer Repository Interface
     *
     * @var \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    protected $customerRepository;

    /**
     * Search Criteria Builder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Order Repository
     *
     * @var \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     */
    protected $orderRepository;

    /**
     * Request Object
     *
     * @var \Magento\Framework\App\RequestInterface $request
     */
    protected $request;

    /**
     * Logger
     *
     * @var \Vonnda\Subscription\Helper\Logger $logger
     */
    protected $logger;
    
    /**
     * 
     * Sales Order Device Info Block
     * 
     * @param Context $context
     * @param SubscriptionCustomerRepository $subscriptionCustomerRepository
     * @param SubscriptionPlanRepository $subscriptionPlanRepository
     * @param DeviceManagerRepositoryInterface $deviceManagerRepository
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepositoryInterface $orderRepository
     * @param RequestInterface $request
     * @param Logger $logger
     * 
     */
    public function __construct(
        Context $context,
        SubscriptionCustomerRepository $subscriptionCustomerRepository,
        SubscriptionPlanRepository $subscriptionPlanRepository,
        DeviceManagerRepositoryInterface $deviceManagerRepository,
        CustomerRepositoryInterface $customerRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRepositoryInterface $orderRepository,
        RequestInterface $request,
        Logger $logger
    ){
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->subscriptionPlanRepository = $subscriptionPlanRepository;
        $this->subscriptionDeviceRepository = $deviceManagerRepository;
        $this->customerRepository = $customerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
        $this->request = $request;
        $this->logger = $logger;
        parent::__construct($context);
	}

    public function getOrderId()
    {
        try {
            $id = $this->request->getParam('order_id');
            if(!$id) return null;

            return $id;
        } catch(\Exception $e){
            return null;
        }
    }

    public function getSubscriptionCustomers()
    {
        try {
            $orderId = $this->getOrderId();
            if(!$orderId) return [];

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(SubscriptionCustomer::PARENT_ORDER_ID, $orderId,'eq')
                ->create();
            return $this->subscriptionCustomerRepository
                ->getList($searchCriteria)
                ->getItems();
        } catch(\Exception $e){
            return [];
        }
    }

    public function getDeviceSerialMap()
    {
        $deviceSerialMap = [];
        $subscriptionCustomers = $this->getSubscriptionCustomers();
        if(!$subscriptionCustomers) return [];

        foreach($subscriptionCustomers as $subscriptionCustomer){
            $device = $subscriptionCustomer->getDevice();
            if(!$device) continue;

            $deviceSerialMap[] = [
                'device_id' => $device->getEntityId(),
                'sku' => $device->getSku(),
                'serial_number' => $device->getSerialNumber() ? 
                    $device->getSerialNumber() 
                    : "Not Available"
            ];
        }

        return $deviceSerialMap;
    }
}