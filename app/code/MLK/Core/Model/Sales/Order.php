<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace MLK\Core\Model\Sales;

use Vonnda\OrderTag\Model\OrderTag;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\{
    Api\Data\OrderInterface as CoreOrderInterface,
    Api\Data\OrderExtensionInterface,
    Model\Order as CoreSalesOrder
};

class Order extends CoreSalesOrder
{
    const PARENT_ORDER_ID = 'parent_order_id';
    const ORDER_TAG_ID = 'order_tag_id';
    const SIGNATURE_REQUIRED = 'signature_required';
    const GIFT_ORDER = 'gift_order';
    const IS_IMPORTANT = 'is_important';


    /**
     * @var OrderTag
     */
    protected $orderTag;

    /**
     * Order constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param CoreSalesOrder\Config $orderConfig
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemCollectionFactory
     * @param \Magento\Catalog\Model\Product\Visibility $productVisibility
     * @param \Magento\Sales\Api\InvoiceManagementInterface $invoiceManagement
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param CoreSalesOrder\Status\HistoryFactory $orderHistoryFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Address\CollectionFactory $addressCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory $paymentCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory $historyCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory $invoiceCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory $memoCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory $trackCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $salesOrderCollectionFactory
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productListFactory
     * @param OrderTag $orderTag
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param ResolverInterface|null $localeResolver
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemCollectionFactory,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Sales\Api\InvoiceManagementInterface $invoiceManagement,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Sales\Model\Order\Status\HistoryFactory $orderHistoryFactory,
        \Magento\Sales\Model\ResourceModel\Order\Address\CollectionFactory $addressCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory $paymentCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory $historyCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory $invoiceCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory $memoCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory $trackCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $salesOrderCollectionFactory,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productListFactory,
        OrderTag $orderTag,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        ResolverInterface $localeResolver = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $timezone,
            $storeManager,
            $orderConfig,
            $productRepository,
            $orderItemCollectionFactory,
            $productVisibility,
            $invoiceManagement,
            $currencyFactory,
            $eavConfig,
            $orderHistoryFactory,
            $addressCollectionFactory,
            $paymentCollectionFactory,
            $historyCollectionFactory,
            $invoiceCollectionFactory,
            $shipmentCollectionFactory,
            $memoCollectionFactory,
            $trackCollectionFactory,
            $salesOrderCollectionFactory,
            $priceCurrency,
            $productListFactory,
            $resource,
            $resourceCollection,
            $data,
            $localeResolver
        );

        $this->orderTag = $orderTag;
    }

    /**
     * {@inheritdoc}
     */
    public function getParentOrderId()
    {
        return $this->getData(self::PARENT_ORDER_ID);
    }


    /**
     * {@inheritdoc}
     */
    public function setParentOrderId($parentOrderId)
    {
        $this->setData(self::PARENT_ORDER_ID, $parentOrderId);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrderTag()
    {
        $orderTagId = $this->getData(self::ORDER_TAG_ID);
        $orderTag = $this->orderTag->load((int)$orderTagId);
        if ($orderTag) {
            return $orderTag->getLabel();
        }
        return null;
    }

    public function getCreatedAtFormattedNoTime($format)
    {
        date_default_timezone_set('America/Los_Angeles');
        // For reference, this calls the config for the store's date and time.
        // $this->timezone->getConfigTimezone('store', $this->getStore()));
        return date('m/d/y', strtotime($this->getCreatedAt()));
    }

    /**
     * {@inheritdoc}
     */
    public function getSignatureRequired(): bool
    {
        return (bool) $this->getData(self::SIGNATURE_REQUIRED);
    }

    /**
     * {@inheritdoc}
     */
    public function setSignatureRequired(bool $signatureRequired): CoreOrderInterface
    {
        $this->setData(self::SIGNATURE_REQUIRED, $signatureRequired);
        return $this;
    }

    /**
     * @return bool
     */
    public function getGiftOrder(): bool
    {
        return (bool) $this->getData(self::GIFT_ORDER);
    }

    /**
     * @param mixed $giftOrder
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function setGiftOrder($giftOrder): CoreOrderInterface
    {
        $this->setData(self::GIFT_ORDER, (bool)$giftOrder);
        return $this;
    }

    /**
     * Retrieve order shipping address
     *
     * @return \Magento\Sales\Model\Order\Address|null
     */
    public function getShippingAddress()
    {
        foreach ($this->getAddresses() as $address) {
            if ($address->getAddressType() == 'shipping' && !$address->isDeleted()) {
                $extensionAttributes = $address->getExtensionAttributes();
                $extensionAttributes->setGiftRecipientEmail($address->getGiftRecipientEmail());

                return $address;
            }
        }
        return null;
    }
    /**
     * Returns product sku
     *
     * @return string
     */
    public function getIsImportant()
    {
         return $this->getData(self::IS_IMPORTANT);
    }
    /**
     * @param $is_important
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function setIsImportant($is_important): CoreOrderInterface
    {
        return $this->setData(self::IS_IMPORTANT, $is_important);
    }


}
