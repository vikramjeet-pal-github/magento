<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace MLK\Core\Model\Sales;

use MLK\Core\Api\Sales\ShipOrderInterface;
use Vonnda\Subscription\Api\SubscriptionCustomerRepositoryInterface;
use Vonnda\DeviceManager\Api\DeviceManagerRepositoryInterface;
use Vonnda\TealiumTags\Helper\ShipmentHelper;
use Vonnda\GiftOrder\Helper\EmailHelper;

use Magento\Sales\Model\ShipOrder as CoreShipOrder;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Shipping\Test\Block\Order\Shipment;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

class ShipOrder implements ShipOrderInterface
{
    protected $coreShipOrder;

    protected $subscriptionCustomerRepository;

    protected $deviceManagerRepository;

    protected $orderItemRepository;

    protected $searchCriteriaBuilder;

    protected $shipmentRepository;

    protected $shipmentHelper;

    protected $orderRepository;

    protected $logger;

    protected $emailHelper;
    
    public function __construct(
        CoreShipOrder $coreShipOrder,
        SubscriptionCustomerRepositoryInterface $subscriptionCustomerRepository,
        DeviceManagerRepositoryInterface $deviceManagerRepository,
        OrderItemRepositoryInterface $orderItemRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ShipmentRepositoryInterface $shipmentRepository,
        ShipmentHelper $shipmentHelper,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger,
        EmailHelper $emailHelper
    ){
        $this->coreShipOrder= $coreShipOrder;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->deviceManagerRepository = $deviceManagerRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->shipmentRepository = $shipmentRepository;
        $this->shipmentHelper = $shipmentHelper;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->emailHelper = $emailHelper;
    }

    /**
     * @param int $orderId
     * @param \MLK\Core\Api\Sales\Data\ShipmentItemCreationInterface[] $items
     * @param bool $notify
     * @param bool $appendComment
     * @param \Magento\Sales\Api\Data\ShipmentCommentCreationInterface|null $comment
     * @param \Magento\Sales\Api\Data\ShipmentTrackCreationInterface[] $tracks
     * @param \Magento\Sales\Api\Data\ShipmentPackageCreationInterface[] $packages
     * @param \Magento\Sales\Api\Data\ShipmentCreationArgumentsInterface|null $arguments
     * @return int
     * @throws \Magento\Sales\Api\Exception\DocumentValidationExceptionInterface
     * @throws \Magento\Sales\Api\Exception\CouldNotShipExceptionInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \DomainException
     */
    public function execute(
        $orderId,
        array $items = [],
        $notify = false,
        $appendComment = false,
        \Magento\Sales\Api\Data\ShipmentCommentCreationInterface $comment = null,
        array $tracks = [],
        array $packages = [],
        \Magento\Sales\Api\Data\ShipmentCreationArgumentsInterface $arguments = null
    ){
        $itemSerialNumberMap = [];

        $giftOrder = $this->getGiftOrder($orderId);
        $shipmentItems = [];

        foreach($items as $item){
            if($item->getSerialNumber()){
                $orderItem = $this->orderItemRepository->get($item->getOrderItemId());
                if($orderItem->getProductType() === 'bundle'){
                    if($giftOrder){
                        $shipmentItems[$orderItem->getSku()] = 
                            [
                                "item" => $orderItem, 
                                "serialNumbers" => $this->getSerialNumberArray($item->getSerialNumber()),
                                "qty" => $item->getQty()
                            ];
                        continue;
                    }
                    $itemSerialNumberMap = $this->setSerialNumberOnDevice($orderItem, $item->getSerialNumber(), $itemSerialNumberMap);
                } else {
                    throw new \Exception('Device serial number passed on non-bundle product');
                }
            }
        }

        $shipmentEntityId =  $this->coreShipOrder->execute(
            $orderId,
            $items,
            $notify,
            $appendComment,
            $comment,
            $tracks,
            $packages,
            $arguments
        );

        if($shipmentEntityId){
            $shipment = $this->shipmentRepository->get($shipmentEntityId);
            //TODO - THE ITEM SERIAL NUMBER MAP FOR TEALIUM WILL HAVE TO BE RE-CREATED WITHOUT USING THE SUBSCRIPTIONS(FOR GIFT ORDERS)
            $this->shipmentHelper->sendShipmentEvent($shipment, $itemSerialNumberMap);
            if($giftOrder){
                $this->sendGiftShipmentEmail($giftOrder, $shipment, $shipmentItems);
            }
        }

        return $shipmentEntityId;
    }

    /**
     * Sets serial number on corresponding device object
     *
     * @param string $serialNumber
     * @param \Magento\Sales\Api\Data\OrderItemInterface $orderItem
     * @return void;
     */
    protected function setSerialNumberOnDevice($orderItem, $shipmentItems, $itemSerialNumberMap)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('parent_order_id',$orderItem->getOrderId(),'eq')
            ->create();
        $subscriptionList = $this->subscriptionCustomerRepository->getList($searchCriteria);
        $serialNumberArray = explode(",", $shipmentItems);
        $childItems = $this->getChildItems($orderItem);
        $idx = 0;
        foreach($subscriptionList->getItems() as $subscription){
            $subscriptionPlan = $subscription->getSubscriptionPlan();
            $device = $subscription->getDevice();
            foreach($childItems->getItems() as $childItem){
                if($childItem->getSku() === $subscriptionPlan->getDeviceSku()){
                    if(!$device->getSerialNumber() && isset($serialNumberArray[$idx])){
                        $itemSerialNumberMap[$childItem->getSku()][] = $serialNumberArray[$idx];
                        $device->setSerialNumber($serialNumberArray[$idx]);
                        $now = new \DateTime();
                        $device->setUpdatedAt($now->format('Y-m-d H:i:s'));
                        $this->deviceManagerRepository->save($device);
                        $idx++;
                    }
                }
            }
        }

        return $itemSerialNumberMap;
    }

    /**
     * Get the order bundle's child items, because the model getChildrenItems returns null
     *
     * @param \Magento\Sales\Api\Data\OrderItemInterface $orderItem
     * @return \Magento\Sales\Api\Data\OrderItemInterface[]
     */
    protected function getChildItems($orderItem)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('parent_item_id',$orderItem->getId(),'eq')
            ->create();
        return $this->orderItemRepository->getList($searchCriteria);
    }

    /**
     * 
     * Return order if marked as gift
     *
     */
    protected function getGiftOrder($orderId)
    {
        try {
            $order = $this->orderRepository->get($orderId);
            if($order->getGiftOrder()){
                return $order;
            }
            return false;
        } catch (\Exception $e){
            return false;
        }
    }

    protected function sendGiftShipmentEmail($order, $shipment, $shipmentItems)
    {
        $this->emailHelper->sendGiftShipmentEmail($order, $shipment, $shipmentItems);
    }

    protected function getSerialNumberArray($serialNumbers)
    {
        $serialArr = explode(",", $serialNumbers);
        return array_map(function($serial){return trim($serial);}, $serialArr);
    }
}