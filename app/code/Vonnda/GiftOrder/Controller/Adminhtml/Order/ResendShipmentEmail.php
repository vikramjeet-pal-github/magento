<?php

namespace Vonnda\GiftOrder\Controller\Adminhtml\Order;

use Vonnda\GiftOrder\Helper\EmailHelper;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;

class ResendShipmentEmail extends Action
{
    /**
     * Json Factory
     *
     * @var \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    protected $resultJsonFactory;

    /**
     * Message Manager
     *
     * @var \Magento\Framework\Message\ManagerInterface $messageManager
     */
    protected $messageManager;

    protected $emailHelper;

    protected $orderRepository;

    protected $orderItemRepository;

    protected $shipmentRepository;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ManagerInterface $messageManager,
        EmailHelper $emailHelper,
        OrderRepositoryInterface $orderRepository,
        OrderItemRepositoryInterface $orderItemRepository,
        ShipmentRepositoryInterface $shipmentRepository
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->messageManager = $messageManager;
        $this->emailHelper = $emailHelper;
        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->shipmentRepository = $shipmentRepository;

        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        if ($this->getRequest()->isAjax()) {
            $params = $this->getRequest()->getParams();
            try {
                if (!isset($params['order_id'])) {
                    throw new \Exception("Invalid request");
                }

                $order = $this->orderRepository->get($params['order_id']);
                $shipments = $order->getShipmentsCollection();
                if(!$shipments->getItems()){
                    throw new \Exception("No shipments have been made for this order");
                }
                foreach($shipments as $shipment){
                    $this->sendEmailForShipment($shipment, $order);
                }

                $response = [
                    'status' => 'success'
                ];
            } catch (\Exception $e) {
                $response = [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }

            return $result->setData($response);
        }
    }

    protected function sendEmailForShipment($shipment, $order)
    {
        $shipmentItems = $shipment->getItems();

        $shipmentItemArr = [];
        foreach ($shipmentItems as $item) {
            $orderItem = $this->orderItemRepository->get($item->getOrderItemId());
            if ($orderItem->getProductType() === 'bundle') {
                $shipmentItemArr[$orderItem->getSku()] =
                    [
                        "item" => $orderItem,
                        "serialNumbers" => [],//These aren't persisted anywhere yet
                        "qty" => (int)$orderItem->getQtyShipped()
                    ];
            }
        }

        $this->emailHelper->sendGiftShipmentEmail($order, $shipment, $shipmentItemArr);
    }
}
