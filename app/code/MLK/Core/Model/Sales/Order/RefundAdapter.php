<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MLK\Core\Model\Sales\Order;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use MLK\Core\Model\Sales\Order\Creditmemo\RefundOperation;
use Magento\Sales\Model\Order\RefundAdapterInterface;

/**
 * @inheritdoc
 */
class RefundAdapter implements RefundAdapterInterface
{
    /**
     * @var RefundOperation
     */
    private $refundOperation;

    /**
     * @param RefundOperation $refundOperation
     */
    public function __construct(
        RefundOperation $refundOperation
    ) {
        $this->refundOperation = $refundOperation;
    }

    /**
     * @inheritdoc
     */
    public function refund(
        CreditmemoInterface $creditmemo,
        OrderInterface $order,
        $isOnline = false
    ) {
        return $this->refundOperation->execute($creditmemo, $order, $isOnline);
    }
}
