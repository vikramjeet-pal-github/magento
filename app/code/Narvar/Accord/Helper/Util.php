<?php

namespace Narvar\Accord\Helper;

use Narvar\Accord\Config\MagentoConfig as MagentoConfig;
use Narvar\Accord\Helper\Constants\Constants;
use Narvar\Accord\Helper\CustomLogger;
use Narvar\Accord\Helper\RepositoryHelper;
use Narvar\Accord\Helper\AccordException;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ProductMetadataInterface;

class Util
{

    private $magentoConfig;

    private $constants;

    private $logger;

    private $repositoryHelper;

    private $searchCriteriaBuilder;

    private $productMetadata;

    public function __construct(
        MagentoConfig $magentoConfig,
        Constants $constants,
        CustomLogger $logger,
        RepositoryHelper $repositoryHelper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductMetadataInterface $productMetadata
    ) {
        $this->magentoConfig = $magentoConfig;
        $this->constants     = $constants->getConstants();
        $this->logger            = $logger;
        $this->repositoryHelper = $repositoryHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productMetadata = $productMetadata;
    }

    /**
     * Method to push data to narvar
     *
     * @param $key  Narvar auth key.
     * @param $data Message to be hashed.
     *
     * @return string
     */
    public function createHmacKey($key, $data)
    {
        /*
            Function:
            hash_hmac ( string $algo , string $data , string $key [, bool $raw_output = FALSE ] ) : string

            Parameters:
            algo
            Name of selected hashing algorithm (i.e. "md5", "sha256", "haval160,4", etc..)
            See hash_hmac_algos() for a list of supported algorithms.

            data
            Message to be hashed.

            key
            Shared secret key used for generating the HMAC variant of the message digest.

            raw_output
            When set to TRUE, outputs raw binary data. FALSE outputs lowercase hexits.
        */

        return base64_encode(hash_hmac("sha256", $data, $key, true));
    }


    public function getRetailerMoniker($storeId)
    {
        return $this->magentoConfig->get(
            $this->constants['RETAILER_MONIKER'],
            $this->constants['STORE_SCOPE'],
            $storeId
        );
    }

    public function getAuthKey($storeId)
    {
        $authKey = $this->magentoConfig->get(
            $this->constants['AUTH_KEY'],
            $this->constants['STORE_SCOPE'],
            $storeId
        );
        if (empty($authKey)) {
            $this->logger->debug(
                'Narvar Auth Handshake not done - auth key missing',
                $storeId
            );
            throw new AccordException('Narvar Auth Handshake not done');
        }
        return $authKey;
    }

    public function getTimezone($storeId)
    {
        $timeZoneKey = 'general/locale/timezone';
        $scope = $this->constants['STORE_SCOPE'];
        return $this->magentoConfig->getConfigValue($timeZoneKey, $storeId, $scope);
    }

    public function getCheckoutLocale($storeId)
    {
        $localeConfigKey = 'general/locale/code';
        $scope = $this->constants['STORE_SCOPE'];
        return $this->magentoConfig->getConfigValue($localeConfigKey, $storeId, $scope);
    }

    public function handleException($ex, $orderId, $storeId, $eventName)
    {
        $exception = (array)$ex;
        $message = 'Exception : ' . $ex->getMessage() . ' occured in Order Plugin';
        $errorLog = [];
        $errorLog['store_id'] = $storeId;
        $errorLog['event_name'] = $eventName;
        $errorLog['order_id'] = $orderId;
        $errorLog['milestone'] = 'end';
        $errorLog['message'] = $message;
        $errorLog['exception'] = $exception;
        $this->logger->error(json_encode($errorLog), $storeId);
    }

    public function logMetadata($orderId, $storeId, $eventName, $milestone)
    {
        $loggingMetadata = [];
        $loggingMetadata['event_name'] = $eventName;
        $loggingMetadata['order_id']   = $orderId;
        $loggingMetadata['store_id']   = $storeId;
        $loggingMetadata['milestone'] = $milestone;
        $this->logger->info(json_encode($loggingMetadata));
    }

    public function getNarvarOrderObject($order, $eventName)
    {
        $billingAddress = $order->getBillingAddress()->getData();
        $orderData = $order->getData();
        $shippingAddress = $order->getShippingAddress()->getData();
        $customerId = $order->getCustomerId();
        $customer = $this->repositoryHelper->getCustomer($customerId);

        $orderItems = $this->getItems($order->getItems(), $order->getStoreId());

        $narvarOrderObject = $orderData;
        $narvarOrderObject['billing_address']  = $billingAddress;
        $narvarOrderObject['customer']         = $customer->getData();
        $narvarOrderObject['customer_group']   = $this->repositoryHelper
                                                  ->getCustomerGroup($customer->getGroupId())->getCode();
        $narvarOrderObject['items']            = $orderItems;
        $narvarOrderObject['shipping_address'] = $shippingAddress;
        $narvarOrderObject['event_name']       = $eventName;
        $narvarOrderObject['timezone']         = $this->getTimezone($order->getStoreId());
        $narvarOrderObject['checkout_locale']  = $this->getCheckoutLocale($order->getStoreId());
        return $narvarOrderObject;
    }

    public function getSearchCriteriaByOrderId($orderId)
    {
        return $this->searchCriteriaBuilder
            ->addFilter('order_id', $orderId)->create();
    }

    public function getOrderShipments($order)
    {
        $shipmentSearchCriteria = $this->getSearchCriteriaByOrderId($order->getEntityId());
        return $this->repositoryHelper->getShipmentDataBySearchCriteria($shipmentSearchCriteria);
    }

    public function getShipmentData($shipment, $storeId)
    {
        $shipmentData  = $shipment->getData();
        $shipmentItems = [];
        foreach ($shipment->getItemsCollection() as $item) {
            $shipmentItem  = $item->getData();
            $product = $this->getProductFromItem($item);
            $shipmentItem['product']['type_id'] = $product->getTypeId();
            $shipmentItems[] = $shipmentItem;
        }
        $shipmentData['items'] = $shipmentItems;
        $trackingInfo = [];
        $tracks = $shipment->getTracksCollection()->addFieldToFilter(
            'parent_id',
            array('eq' => $shipment->getId())
        );
        foreach ($tracks as $trackitem) {
            $trackingInfo[] = $trackitem->getData();
        }
        $shipmentData['tracks'] = $trackingInfo;
        $shipmentData['source_data'] = $this->getSourceData($shipment);
        return $shipmentData;
    }

    public function getSourceData($shipment)
    {
        if (!(strpos($this->getMagentoVersion(), '2.2') === 0)) {
            $extensionAttributes = $shipment->getExtensionAttributes();
            if (
                !is_null($extensionAttributes)
                && is_object($extensionAttributes)
                && method_exists($extensionAttributes, 'getSourceCode')
            ) {
                $sourceCode = $extensionAttributes->getSourceCode();
                if (!is_null($sourceCode)) {
                    return $this->repositoryHelper->getSourceData($sourceCode);
                }
            }
        }
        return null;
    }

    public function getOrderInvoices($order)
    {
        $invoiceSearchCriteria = $this->getSearchCriteriaByOrderId($order->getEntityId());
        return $this->repositoryHelper->getInvoiceDataBySearchCriteria($invoiceSearchCriteria);
    }

    public function getInvoiceData($invoice, $storeId)
    {
        $invoiceData  = $invoice->getData();
        $invoiceItems = [];
        foreach ($invoice->getItemsCollection() as $item) {
            $invoiceItems[] = $item->getData();
        }
        $invoiceData['items'] = $invoiceItems;
        return $invoiceData;
    }

    public function getItems($items, $storeId)
    {
        $orderItems = [];
        foreach ($items as $item) {
            $tmpArray  = $item->getData();
            $product = $this->getProductFromItem($item);
            $tmpArray['product_options'] = $item->getProductOptions();
            $tmpArray['product']         = $this->getProductData($product);
            $tmpArray['url']             = $this->getProductUrls($product, $storeId);
            $tmpArray['categories']      = $this->getProductCategories($product);
            $orderItems[] = $tmpArray;
        }
        return $orderItems;
    }

    public function getProductFromItem($item)
    {
        $productId = $item->getProductId();
        return $this->repositoryHelper->getProduct($productId);
    }

    public function getProductUrls($product, $storeId)
    {
        $urls = [];
        $store   = $this->repositoryHelper->getStore($storeId);
        if (!empty($product->getImage())) {
            $urls['image'] = $store->getBaseUrl('media') . 'catalog/product' . $product->getImage();
        } else {
            $urls['image'] = '';
        }
        $urls['item'] = $product->getProductUrl();
        return $urls;
    }

    public function getProductCategories($product)
    {
        $categoryIds = $product->getCategoryIds();
        $categoryNames = [];
        if (is_array($categoryIds) && !empty($categoryIds)) {
            $categories = $this->repositoryHelper->getCategories($categoryIds);
            foreach ($categories as $category) {
                $categoryNames[] = $category->getName();
            }
        }
        return $categoryNames;
    }

    public function getProductData($product)
    {
        $productData = $product->getData();
    
        $attributes = $product->getCustomAttributes();
        foreach ($attributes as $attribute) {
            try {
                $productData[$attribute->getAttributeCode()] = $product->getResource()
                ->getAttribute($attribute->getAttributeCode())->getFrontend()->getValue($product);
            } catch (\Exception $e) {
                $this->logger->info('unable to fetch value for attribute code ' . $attribute->getAttributeCode()
                . ' in ' . __METHOD__);
            }
        }
        return $productData;
    }

    public function isDuplicateOrderCall($order)
    {
        if ($order->getStatus() == 'processing' || $order->getStatus() == 'complete') {
            $shipments = $this->getOrderShipments($order);
            if (!empty($shipments)) {
                return true;
            }
        }
        return false;
    }

    public function getMagentoVersion()
    {
        return $this->productMetadata->getVersion();
    }
}
