<?php

namespace Vonnda\TealiumTags\Plugin\Subscription\Controller\Adminhtml\Subscriptioncustomer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Sales\Api\OrderRepositoryInterface;
use Throwable;
use Vonnda\DeviceManager\Model\Data\DeviceManager;
use Vonnda\Subscription\Controller\Adminhtml\Subscriptioncustomer\Edit as OriginalController;
use Vonnda\Subscription\Helper\Logger;
use Vonnda\Subscription\Model\SubscriptionCustomer;
use Vonnda\Subscription\Model\SubscriptionCustomerRepository;
use Vonnda\TealiumTags\Helper\Data as DataObjectHelper;
use Vonnda\TealiumTags\Model\HttpGateway;

class Edit
{
    /**
     * @var HttpGateway $customerSession
     */
    protected $httpGateway;

    /**
     * @var Logger $logger
     */
    protected $logger;

    /**
     * @var SubscriptionCustomerRepository
     */
    protected $subscriptionCustomerRepository;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var DataObjectHelper $dataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @param  HttpGateway  $httpGateway
     * @param  Logger  $logger
     * @param  SubscriptionCustomerRepository  $subscriptionCustomerRepository
     * @param  OrderRepositoryInterface  $orderRepository
     * @param  DataObjectHelper  $dataObjectHelper
     */
    public function __construct(
            HttpGateway $httpGateway,
            Logger $logger,
            SubscriptionCustomerRepository $subscriptionCustomerRepository,
            OrderRepositoryInterface $orderRepository,
            DataObjectHelper $dataObjectHelper
    ) {
        $this->httpGateway = $httpGateway;
        $this->logger = $logger;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->orderRepository = $orderRepository;
        $this->dataObjectHelper = $dataObjectHelper;
    }

    /**
     * @param  OriginalController  $subject
     * @param  callable  $proceed
     *
     * @return mixed
     */
    public function aroundExecute(OriginalController $subject, callable $proceed)
    {
        //@todo refactor the original controller to prevent the extra queries here to get the status from db

        $subscriptionCustomerData = $subject->getRequest()->getParam('subscriptionCustomer');
        if (is_array($subscriptionCustomerData)) {
            $initialSubscriptionStatus = SubscriptionCustomer::RETURNED_STATUS;
            try {
                // retrieve the original status before the edit
                $subscriptionCustomer = $this->subscriptionCustomerRepository->getById($subscriptionCustomerData['id']);
                $initialSubscriptionStatus = $subscriptionCustomer->getStatus();
            } catch (Throwable $e) {
                //  if something is wrong, the controller will show the error message
            }

            if ($initialSubscriptionStatus !== SubscriptionCustomer::RETURNED_STATUS) {
                // now that we have initial status which is not refunded, let the controller do it's thing
                $result = $proceed();

                try {
                    // reload the subscription customer to get the final status
                    $subscriptionCustomer = $this->subscriptionCustomerRepository->getById($subscriptionCustomerData['id']);
                    $finalSubscriptionStatus = $subscriptionCustomer->getStatus();

                    // the status has now changed to returned, so send event to tealium
                    if ($finalSubscriptionStatus === SubscriptionCustomer::RETURNED_STATUS) {
                        $this->sendTealiumEvent($subscriptionCustomer);
                    }
                } catch (Throwable $e) {
                    //  if something goes wrong here, let the controller result get returned
                }

                return $result;
            }
        }

        return $proceed();
    }

    /**
     * @param  SubscriptionCustomer  $subscriptionCustomer
     */
    protected function sendTealiumEvent($subscriptionCustomer)
    {
        $orderId = $subscriptionCustomer->getParentOrderId();
        $order = $this->orderRepository->get($orderId);
        /** @var DeviceManager $device */
        $device = $subscriptionCustomer->getDevice();
        if ($device instanceof DeviceManager) {
            $categoryName = '';
            /** @var ProductInterface $product */
            $product = $device->getAssociatedProduct();
            /** @var Collection $collection */
            $collection = $product->getCategoryCollection();
            $collection->addAttributeToSelect('name');
            $collection->addIsActiveFilter();
            /** @var Category $category */
            foreach ($collection as $category) {
                if ($category && $category->getName()) {
                    $categoryName = $category->getName();
                }
            }

            $utagData = [];
            $utagData['event_action'] = 'Refund';
            $utagData['event_category'] = 'Offline Ecommerce';
            $utagData['tealium_event'] = 'return_product_api';
            $utagData['event_label'] = [$device->getSku()];

            $utagData['customer_email'] = $order->getCustomerEmail();
            $utagData['customer_uid'] = $this->dataObjectHelper->getCustomerUid($order->getCustomerId());
            $utagData['product_category'] = [$categoryName];
            $utagData['product_name'] = [$device->getAssociatedProductName()];
            $utagData['product_quantity'] = [1];
            $utagData['product_sku'] = [$device->getSku()];
            $utagData['serial_number'] = $device->getSerialNumber();

            $success = $this->httpGateway->pushTag($utagData);
            if (!$success) {
                $this->logger->info("Failure sending utagData for subscription order refund event, order ID: ".$orderId);
            }
        } else {
            $this->logger->info("Failure sending utagData for subscription refund event because no device manager.");
        }
    }
}