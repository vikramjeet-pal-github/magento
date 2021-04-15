<?php

namespace Narvar\Accord\Plugin;

use Narvar\Accord\Helper\CustomLogger;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Narvar\Accord\Helper\Processor;
use Magento\Catalog\Model\ProductRepository;
use Magento\Customer\Model\CustomerFactory;
use Narvar\Accord\Helper\Util;
use Narvar\Accord\Helper\NoFlakeLogger;

define('NARVAR_ORDER_EVENT', 'narvar_order_plugin');

class Order
{

    private $logger;

    private $jsonHelper;

    private $processor;

    private $productRepository;

    private $customerFactory;

    private $util;

    private $noFlakeLogger;

    /**
     * Constructor
     *
     * @param JsonHelper        $jsonHelper        JsonHelper for json encoding
     * @param CustomLogger      $logger            Custom logger
     * @param Processor         $processor         Send order data to narvar
     * @param ProductRepository $productRepository get product details by product id
     * @param CustomerFactory   $customerFactory   get customer details by customer id
     */
    public function __construct(
        JsonHelper $jsonHelper,
        CustomLogger $logger,
        Processor $processor,
        ProductRepository $productRepository,
        CustomerFactory $customerFactory,
        Util $util,
        NoFlakeLogger $noFlakeLogger
    ) {
        $this->jsonHelper        = $jsonHelper;
        $this->logger            = $logger;
        $this->processor         = $processor;
        $this->productRepository = $productRepository;
        $this->customerFactory   = $customerFactory;
        $this->util              = $util;
        $this->noFlakeLogger     = $noFlakeLogger;
    }


    /**
     * Method triggered after Magento\Sales\Api\OrderRepositoryInterface execution
     *
     * @param $orderRepo magneto orderRepo interface this parameter is not used but is passed to the afterSave function.
     * @param $order     magneto order class instance
     *
     * @return void
     */
    public function afterSave(\Magento\Sales\Api\OrderRepositoryInterface $orderRepo, $order)
    {
        $orderId = $order->getIncrementId();
        $storeId = $order->getStoreId();
        $eventName = NARVAR_ORDER_EVENT;
        try {
            $this->util->logMetadata($orderId, $storeId, $eventName, 'start');
            $retailerMoniker = $this->util->getRetailerMoniker($storeId);
            if (!$this->util->isDuplicateOrderCall($order) && !empty($retailerMoniker)) {
                $narvarOrderObject = $this->util->getNarvarOrderObject(
                    $order,
                    $eventName
                );
                $this->processor->sendPluginData($narvarOrderObject, $retailerMoniker, $eventName);
                $this->noFlakeLogger->logNoFlakeData(
                    $narvarOrderObject,
                    $eventName,
                    $retailerMoniker
                );
            } elseif (empty($retailerMoniker)) {
                $this->util->logMetadata(
                    $orderId,
                    $storeId,
                    $eventName,
                    'end - Narvar Accord Not Configured for this Store Id'
                );
            } else {
                $this->util->logMetadata($orderId, $storeId, $eventName, 'end - skip duplicate call');
            }
        } catch (\Exception $ex) {
            $this->util->handleException($ex, $orderId, $storeId, $eventName);
        } finally {
            return $order;
        }
    }
}
