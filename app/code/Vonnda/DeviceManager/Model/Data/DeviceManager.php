<?php

namespace Vonnda\DeviceManager\Model\Data;

use Vonnda\DeviceManager\Api\Data\DeviceManagerInterface;

use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Catalog\Helper\ImageFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class DeviceManager extends \Magento\Framework\Api\AbstractExtensibleObject implements DeviceManagerInterface
{

    protected $productRepository;

    protected $searchCriteriaBuilder;

    /**
     * Image Helper
     *
     * @var ImageFactory $imageHelper
     */
    protected $imageHelper;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        ImageFactory $imageHelper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $attributeValueFactory,
        $data = []
    ) {
        $this->productRepository = $productRepository;
        $this->imageHelper = $imageHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;

        parent::__construct(
            $extensionFactory,
            $attributeValueFactory,
            $data
        );
    }

    /**
     * Get entity_id
     * @return string|null
     */
    public function getDevicemanagerId()
    {
        return $this->_get(self::entity_id);
    }

    /**
     * Set entity_id
     * @param string $devicemanagerId
     * @return \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface
     */
    public function setDevicemanagerId($devicemanagerId)
    {
        return $this->setData(self::entity_id, $devicemanagerId);
    }

    /**
     * Get entity_id
     * @return string|null
     */
    public function getEntityId()
    {
        return $this->_get(self::ENTITY_ID);
    }

    /**
     * Set entity_id
     * @param string $entityId
     * @return \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Vonnda\DeviceManager\Api\Data\DeviceManagerExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     * @param \Vonnda\DeviceManager\Api\Data\DeviceManagerExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Vonnda\DeviceManager\Api\Data\DeviceManagerExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }

    /**
     * Get serial_number
     * @return string|null
     */
    public function getSerialNumber()
    {
        return $this->_get(self::SERIAL_NUMBER);
    }

    /**
     * Set serial_number
     * @param string $serialNumber
     * @return \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface
     */
    public function setSerialNumber($serialNumber)
    {
        return $this->setData(self::SERIAL_NUMBER, $serialNumber);
    }

    /**
     * Get sales_channel
     * @return string|null
     */
    public function getSalesChannel()
    {
        return $this->_get(self::SALES_CHANNEL);
    }

    /**
     * Set sales_channel
     * @param string $salesChannel
     * @return \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface
     */
    public function setSalesChannel($salesChannel)
    {
        return $this->setData(self::SALES_CHANNEL, $salesChannel);
    }

    /**
     * Get sku
     * @return string|null
     */
    public function getSku()
    {
        return $this->_get(self::SKU);
    }

    /**
     * Set sku
     * @param string $sku
     * @return \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface
     */
    public function setSku($sku)
    {
        return $this->setData(self::SKU, $sku);
    }

    /**
     * Get purchase_date
     * @return string|null
     */
    public function getPurchaseDate()
    {
        return $this->_get(self::PURCHASE_DATE);
    }

    /**
     * Set purchase_date
     * @param string $purchaseDate
     * @return \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface
     */
    public function setPurchaseDate($purchaseDate)
    {
        return $this->setData(self::PURCHASE_DATE, $purchaseDate);
    }

    /**
     * Get customer_id
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->_get(self::CUSTOMER_ID);
    }

    /**
     * Set customer_id
     * @param string $customerId
     * @return \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->_get(self::CREATED_AT);
    }

    /**
     * Set created_at
     * @param string $createdAt
     * @return \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Get updated_at
     * @return string|null
     */
    public function getUpdatedAt()
    {
        return $this->_get(self::UPDATED_AT);
    }

    /**
     * Set updated_at
     * @param string $updatedAt
     * @return \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * {@inheritdoc}
     */
    public function getAssociatedProductName()
    {
        if(!$this->getSku()){
            return null;
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('sku', $this->getSku(),'eq')
            ->create();
        $productList = $this->productRepository->getList($searchCriteria);
        foreach($productList->getItems() as $product){
            return $product->getName();
        }
        return null;
    }

    public function getAssociatedProductImage()
    {
        if(!$this->getSku()){
            return null;
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('sku', $this->getSku(),'eq')
            ->create();
        $productList = $this->productRepository->getList($searchCriteria);
        foreach($productList->getItems() as $product){
            $imageUrl = $this->imageHelper->create()
            ->init($product, 'product_base_image')->getUrl();
            return $imageUrl;
        }
        return null;
    }

    public function getAssociatedProduct()
    {
        if(!$this->getSku()){
            return null;
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('sku', $this->getSku(),'eq')
            ->create();
        $productList = $this->productRepository->getList($searchCriteria);
        foreach($productList->getItems() as $product){
            return $product;
        }
        return null;
    }

     /**
     * {@inheritdoc}
     */
    public function setAssociatedProductName($associatedProductName)
    {
        return true;
    }

    /** {@inheritdoc} */
    public function getIsSerialNumberValid()
    {
        return $this->_get(self::IS_SERIAL_NUMBER_VALID);
    }

    /** {@inheritdoc} */
    public function setIsSerialNumberValid($isSerialNumberValid)
    {
        return $this->setData(self::IS_SERIAL_NUMBER_VALID, $isSerialNumberValid);
    }

}
