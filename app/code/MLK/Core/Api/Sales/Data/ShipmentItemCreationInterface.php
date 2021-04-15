<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace MLK\Core\Api\Sales\Data;


interface ShipmentItemCreationInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Get Serial Number
     * 
     * @param void
     * @return string
     */
    public function getSerialNumber();
    
    /**
     * Set Serial Number
     * 
     * @param string $serialNumber
     * @return $this
     */
    public function setSerialNumber($serialNumber);

    /**
     * Gets the order item ID for the item.
     *
     * @return int Order item ID.
     * @since 100.1.2
     */
    public function getOrderItemId();

    /**
     * Sets the order item ID for the item.
     *
     * @param int $id
     * @return $this
     * @since 100.1.2
     */
    public function setOrderItemId($id);

    /**
     * Gets the quantity for the item.
     *
     * @return float Quantity.
     * @since 100.1.2
     */
    public function getQty();

    /**
     * Sets the quantity for the item.
     *
     * @param float $qty
     * @return $this
     * @since 100.1.2
     */
    public function setQty($qty);

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Magento\Sales\Api\Data\ShipmentItemCreationExtensionInterface|null
     * @since 100.1.2
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Magento\Sales\Api\Data\ShipmentItemCreationExtensionInterface $extensionAttributes
     * @return $this
     * @since 100.1.2
     */
    public function setExtensionAttributes(
        \Magento\Sales\Api\Data\ShipmentItemCreationExtensionInterface $extensionAttributes
    );



}