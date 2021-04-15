<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MLK\Core\Api\Sales;

/**
 * Class ShipOrderInterface
 *
 * @api
 * @since 100.1.2
 */
interface ShipOrderInterface
{
    /**
     * Creates new Shipment for given Order.  We are overriding the ShipmentItemCreationInterfaceClass
     *
     * @param int $orderId
     * @param \MLK\Core\Api\Sales\Data\ShipmentItemCreationInterface[] $items
     * @param bool $notify
     * @param bool $appendComment
     * @param \Magento\Sales\Api\Data\ShipmentCommentCreationInterface|null $comment
     * @param \Magento\Sales\Api\Data\ShipmentTrackCreationInterface[] $tracks
     * @param \Magento\Sales\Api\Data\ShipmentPackageCreationInterface[] $packages
     * @param \Magento\Sales\Api\Data\ShipmentCreationArgumentsInterface|null $arguments
     * @return int Id of created Shipment.
     * @since 100.1.2
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
    );
}