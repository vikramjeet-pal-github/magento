<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace MLK\Core\Model\Sales\Order\Shipment;

use MLK\Core\Api\Sales\Data\ShipmentItemCreationInterface;

class ItemCreation implements ShipmentItemCreationInterface
{
    /**
     * @var string
     */
    private $serialNumber;
    
    /**
     * @var int
     */
    private $orderItemId;

    /**
     * @var float
     */
    private $qty;

     /**
     * @var \Magento\Sales\Api\Data\ShipmentItemCreationExtensionInterface
     */
    private $extensionAttributes;

    /**
     * {@inheritdoc}
     */
    public function getSerialNumber()
    {
        return $this->serialNumber;
    }

    /**
     * {@inheritdoc}
     */
    public function setSerialNumber($serialNumber)
    {
        $this->serialNumber = $serialNumber;
        return $this;
    }

    //@codeCoverageIgnoreStart

    /**
     * {@inheritdoc}
     */
    public function getOrderItemId()
    {
        return $this->orderItemId;
    }

    /**
     * {@inheritdoc}
     */
    public function setOrderItemId($orderItemId)
    {
        $this->orderItemId = $orderItemId;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getQty()
    {
        return $this->qty;
    }

    /**
     * {@inheritdoc}
     */
    public function setQty($qty)
    {
        $this->qty = $qty;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Magento\Sales\Api\Data\ShipmentItemCreationExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->extensionAttributes;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Magento\Sales\Api\Data\ShipmentItemCreationExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Magento\Sales\Api\Data\ShipmentItemCreationExtensionInterface $extensionAttributes
    ) {
        $this->extensionAttributes = $extensionAttributes;
        return $this;
    }

}