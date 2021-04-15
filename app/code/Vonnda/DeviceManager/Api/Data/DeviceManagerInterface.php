<?php
/**
 * A Magento 2 module named Vonnda/DeviceManager
 * Copyright (C) 2018  
 * 
 * This file is part of Vonnda/DeviceManager.
 * 
 * Vonnda/DeviceManager is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Vonnda\DeviceManager\Api\Data;

interface DeviceManagerInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{

    const UPDATED_AT = 'updated_at';
    const PURCHASE_DATE = 'purchase_date';
    const SKU = 'sku';
    const ENTITY_ID = 'entity_id';
    const SERIAL_NUMBER = 'serial_number';
    const entity_id = 'entity_id';
    const CUSTOMER_ID = 'customer_id';
    const CREATED_AT = 'created_at';
    const SALES_CHANNEL = 'sales_channel';
    const IS_SERIAL_NUMBER_VALID = 'is_serial_number_valid';

    /**
     * Get entity_id
     * @return string|null
     */
    public function getDevicemanagerId();

    /**
     * Set entity_id
     * @param string $devicemanagerId
     * @return \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface
     */
    public function setDevicemanagerId($devicemanagerId);

    /**
     * Get entity_id
     * @return string|null
     */
    public function getEntityId();

    /**
     * Set entity_id
     * @param string $entityId
     * @return \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface
     */
    public function setEntityId($entityId);

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Vonnda\DeviceManager\Api\Data\DeviceManagerExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param \Vonnda\DeviceManager\Api\Data\DeviceManagerExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Vonnda\DeviceManager\Api\Data\DeviceManagerExtensionInterface $extensionAttributes
    );

    /**
     * Get serial_number
     * @return string|null
     */
    public function getSerialNumber();

    /**
     * Set serial_number
     * @param string $serialNumber
     * @return \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface
     */
    public function setSerialNumber($serialNumber);

    /**
     * Get sales_channel
     * @return string|null
     */
    public function getSalesChannel();

    /**
     * Set sales_channel
     * @param string $salesChannel
     * @return \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface
     */
    public function setSalesChannel($salesChannel);

    /**
     * Get sku
     * @return string|null
     */
    public function getSku();

    /**
     * Set sku
     * @param string $sku
     * @return \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface
     */
    public function setSku($sku);

    /**
     * Get purchase_date
     * @return string|null
     */
    public function getPurchaseDate();

    /**
     * Set purchase_date
     * @param string $purchaseDate
     * @return \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface
     */
    public function setPurchaseDate($purchaseDate);

    /**
     * Get customer_id
     * @return string|null
     */
    public function getCustomerId();

    /**
     * Set customer_id
     * @param string $customerId
     * @return \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface
     */
    public function setCustomerId($customerId);

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     * @param string $createdAt
     * @return \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * Get updated_at
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set updated_at
     * @param string $updatedAt
     * @return \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface
     */
    public function setUpdatedAt($updatedAt);

    /**
     * Get Product Name
     * @param void
     * @return string|null
     */
    public function getAssociatedProductName();

     /**
     * Set Associated Product Name
     * @param string $associatedProductName
     * @return \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface
     */
    public function setAssociatedProductName($associatedProductName);

    /**
     * @return int|null
     */
    public function getIsSerialNumberValid();

    /**
     * @param int|null $isSerialNumberValid
     * @return DeviceManagerInterface
     */
    public function setIsSerialNumberValid($isSerialNumberValid);

}
