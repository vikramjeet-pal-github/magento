<?php

/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\TealiumTags\Helper;

use Vonnda\TealiumTags\Helper\Data as DataObjectHelper;
use Vonnda\TealiumTags\Model\HttpGateway;
use Vonnda\Subscription\Helper\AddressHelper;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;


class ShipmentHelper extends AbstractHelper
{
    protected $httpGateway;

    protected $dataObjectHelper;

    protected $customerRepository;

    protected $orderRepository;

    protected $orderItemRepository;

    protected $productRepository;

    protected $logger;

    protected $addressHelper;
    /**
     * 
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        HttpGateway $httpGateway,
        DataObjectHelper $dataObjectHelper,
        CustomerRepositoryInterface $customerRepository,
        OrderRepositoryInterface $orderRepository,
        OrderItemRepositoryInterface $orderItemRepository,
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger,
        Context $context,
        AddressHelper $addressHelper
    ) {
        $this->dataObjectHelper = $dataObjectHelper;
        $this->httpGateway = $httpGateway;
        $this->customerRepository = $customerRepository;
        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->addressHelper = $addressHelper;
        parent::__construct($context);
    }

    public function sendShipmentEvent($shipment, $itemSerialNumberMap = [])
    {
        if (!$this->shouldSendEvent()) {
            return;
        }

        $order = $shipment->getOrder();

        try {
            $customer = $this->customerRepository->getById($order->getCustomerId());
            $uuid = $customer->getCustomAttribute('cognito_uuid');
        } catch(\Exception $e){
            $customer = null;
            $uuid = null;
        }
        
        try {
            $utagData = [
                "shipment_date" => $shipment->getCreatedAt(),
                "customer_email" => $customer ? $customer->getEmail() : $order->getCustomerEmail(),
                "serial_number" => [],
                'customer_id' => $customer ? $customer->getId() : null,
                'customer_uid' => ($uuid && $uuid->getValue()) ? $uuid->getValue() : "",
            ];
            $utagData = $this->setEventFields($utagData);
            $utagData = $this->dataObjectHelper->addSiteInfo($utagData);

            $utagData = $this->setShipmentFields($utagData, $shipment);

            $address = $shipment->getShippingAddress();
            $utagData = $this->setShippingAddressFields($utagData, $address);

            $salesShipmentItems = $shipment->getItems();
            $order = $this->orderRepository->get($shipment->getOrderId());
            $utagData = $this->setProductFields($utagData, $order, $salesShipmentItems, $itemSerialNumberMap);

            $success = $this->httpGateway->pushTag($utagData);
            if (!$success) {
                $this->logger->info("Failure sending utagData for shipment " . $shipment->getId() . ", shipOrderEvent.");
            }
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }

    //Might have to check some criteria in the future
    protected function shouldSendEvent()
    {
        return true;
    }

    protected function setEventFields($utagData)
    {
        $utagData['event_action'] = 'Product Shipped';
        $utagData['event_category'] = 'Offline Ecommerce';
        $utagData['tealium_event'] = 'product_shipped_api';
        return $utagData;
    }

    protected function setShipmentFields($utagData, $shipment)
    {
        try {
            $tracks = $shipment->getTracks();
            $carrierCode = "";
            if (isset($tracks[0])) {
                $carrierCode = $tracks[0]->getCarrierCode();
            }

            $utagData['shipment_id'] = $shipment->getId();
            $utagData['shipment_type'] = $carrierCode;
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
        return $utagData;
    }

    protected function setShippingAddressFields($utagData, $address)
    {
        try {
            $region = $this->addressHelper->getRegionInterface($address->getRegionId());
            $regionCode = $region->getRegionCode();

            $countryId = $address->getCountryId() ? strtolower($address->getCountryId()) : "";

            $street = $address->getStreet();
            $streetOne = isset($street[0]) ? $street[0] : "";
            $streetTwo = isset($street[1]) ? $street[1] : "";
            $utagData['customer_address_1_shipping'] = $streetOne;
            $utagData['customer_address_2_shipping'] = $streetTwo;
            $utagData['customer_zip_shipping'] = $address->getPostcode();
            $utagData['customer_city_shipping'] = $address->getCity();
            $utagData['customer_state_shipping'] = $regionCode;
            $utagData['customer_country_shipping'] = $this->dataObjectHelper->getCountryFromId($address->getCountryId());
            $utagData['customer_country_code_shipping'] = $countryId;
            $utagData['customer_first_name_shipping'] = $address->getFirstname();
            $utagData['customer_last_name_shipping'] = $address->getLastname();
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
        return $utagData;
    }

    protected function setProductFields($utagData, $order, $salesShipmentItems, $itemSerialNumberMap)
    {
        foreach ($salesShipmentItems as $salesShipmentItem) {
            try {
                $product = $this->productRepository->getById($salesShipmentItem->getProductId());
                $salesOrderItem = $this->orderItemRepository->get($salesShipmentItem->getOrderItemId());

                $productType = $salesOrderItem->getProductType();
                if (!($productType === 'simple')){
                    continue;
                }

                $qty = $salesShipmentItem->getQty();
                $serialIndex = 0;
                for ($x = 0; $x < $qty; $x++) {
                    $productCategories = $this->dataObjectHelper->getProductCategories($product);
                    $utagData['product_brand'][] = 'Molekule';
                    $utagData['product_discount_amount'][] = $salesOrderItem->getDiscountAmount() ?
                        number_format($salesOrderItem->getDiscountAmount(), 2, '.', '') : "";

                    $utagData['product_image_url'][] = $this->dataObjectHelper->getProductImageUrl($product);
                    $utagData['product_list_price'][] = $salesOrderItem->getOriginalPrice() ?
                        number_format($salesOrderItem->getOriginalPrice(), 2, '.', '') : "";

                    $utagData['product_name'][] = $salesShipmentItem->getName();
                    $utagData['product_price'][] = $salesOrderItem->getPrice() ?
                        number_format($salesOrderItem->getPrice(), 2, '.', '') : "";

                    $utagData['product_promo_code'][] = $order->getCouponCode() ? $order->getCouponCode() : "";
                    $utagData['product_quantity'][] = 1;
                    $utagData['product_sku'][] = $product->getSku();
                    $utagData['product_category'][] = $productCategories['subcategory'];
                    $utagData['product_id'][] = $product->getId();

                    if ($itemSerialNumberMap) {
                        $itemHasSerialNumber = isset($itemSerialNumberMap[$product->getSku()]) &&
                            isset($itemSerialNumberMap[$product->getSku()][$serialIndex]) &&
                            $itemSerialNumberMap[$product->getSku()][$serialIndex];
                        if ($itemHasSerialNumber) {
                            $utagData['serial_number'][] = $itemSerialNumberMap[$product->getSku()][$serialIndex];
                            $serialIndex++;
                        } else {
                            $utagData['serial_number'][] = "";
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->logger->info($e->getMessage());
            }
        }

        return $utagData;
    }
}
