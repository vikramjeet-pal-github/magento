<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MLK\Core\Model\Sales\Order;

use Magento\Bundle\Ui\DataProvider\Product\Listing\Collector\BundlePrice;
use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * Factory class for @see \Magento\Sales\Model\Order\Creditmemo
 */
class CreditmemoFactory extends \Magento\Sales\Model\Order\CreditmemoFactory
{
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * Check if order item can be refunded
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @param array $qtys
     * @param array $invoiceQtysRefundLimits
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function canRefundItem($item, $qtys = [], $invoiceQtysRefundLimits = [])
    {
        if ($item->isDummy()) {
            if ($item->getHasChildren()) {
                foreach ($item->getChildrenItems() as $child) {
                    if (empty($qtys)) {
                        if ($this->canRefundNoDummyItem($child, $invoiceQtysRefundLimits)) {
                            return true;
                        }
                    } else {
                        if (isset($qtys[$child->getId()])) {
                            return true;
                        }
                    }
                }
                return false;
            } elseif ($item->getParentItem()) {
                $parent = $item->getParentItem();
                if (empty($qtys)) {
                    return $this->canRefundNoDummyItem($parent, $invoiceQtysRefundLimits);
                } else {
                    return isset($qtys[$parent->getId()]);
                }
            }
        } else {
            return $this->canRefundNoDummyItem($item, $invoiceQtysRefundLimits);
        }
    }

    /**
     * @param Item $orderItem
     * @param int $parentQty
     * @return int
     */
    private function calculateProductOptions(Item $orderItem, int $parentQty): int
    {
        $qty = $parentQty;
        $productOptions = $orderItem->getProductOptions();
        if (isset($productOptions['bundle_selection_attributes'])) {
            $bundleSelectionAttributes = $this->serializer->unserialize(
                $productOptions['bundle_selection_attributes']
            );
            if ($bundleSelectionAttributes) {
                $qty = $bundleSelectionAttributes['qty'] * $parentQty;
            }
        }
        return $qty;
    }

    /**
     * Gets list of quantities based on invoice refunded items.
     *
     * @param Invoice $invoice
     * @return array
     */
    private function getInvoiceRefundedQtyList(Invoice $invoice): array
    {
        $invoiceRefundedQtyList = [];
        foreach ($invoice->getOrder()->getCreditmemosCollection() as $creditmemo) {
            if ($creditmemo->getState() !== Creditmemo::STATE_CANCELED &&
                $creditmemo->getInvoiceId() === $invoice->getId()
            ) {
                foreach ($creditmemo->getAllItems() as $creditmemoItem) {
                    $orderItemId = $creditmemoItem->getOrderItem()->getId();
                    if (isset($invoiceRefundedQtyList[$orderItemId])) {
                        $invoiceRefundedQtyList[$orderItemId] += $creditmemoItem->getQty();
                    } else {
                        $invoiceRefundedQtyList[$orderItemId] = $creditmemoItem->getQty();
                    }
                }
            }
        }

        return $invoiceRefundedQtyList;
    }

    /**
     * Gets limits of refund based on invoice items.
     *
     * @param Invoice $invoice
     * @return array
     */
    private function getInvoiceRefundLimitsQtyList(Invoice $invoice): array
    {
        $invoiceRefundLimitsQtyList = [];
        $invoiceRefundedQtyList = $this->getInvoiceRefundedQtyList($invoice);

        foreach ($invoice->getAllItems() as $invoiceItem) {
            $qtyCanBeRefunded = $invoiceItem->getQty();
            $orderItemId = $invoiceItem->getOrderItem()->getId();
            if (isset($invoiceRefundedQtyList[$orderItemId])) {
                $qtyCanBeRefunded = $qtyCanBeRefunded - $invoiceRefundedQtyList[$orderItemId];
            }
            $invoiceRefundLimitsQtyList[$orderItemId] = $qtyCanBeRefunded;
        }

        return $invoiceRefundLimitsQtyList;
    }

    /**
     * Gets quantity of items to refund based on order item.
     *
     * @param Item $orderItem
     * @param array $qtyList
     * @param array $refundLimits
     * @return float
     */
    private function getQtyToRefund(Item $orderItem, array $qtyList, array $refundLimits = []): float
    {
        $qty = 0;
        if ($orderItem->isDummy()) {
            if (isset($qtyList[$orderItem->getParentItemId()])) {
                $parentQty = $qtyList[$orderItem->getParentItemId()];
            } elseif ($orderItem->getProductType() === BundlePrice::PRODUCT_TYPE) {
                $parentQty = $orderItem->getQtyInvoiced();
            } else {
                $parentQty = $orderItem->getParentItem() ? $orderItem->getParentItem()->getQtyToRefund() : 1;
            }
            $qty = $this->calculateProductOptions($orderItem, $parentQty);
        } else {
            if (isset($qtyList[$orderItem->getId()])) {
                $qty = $qtyList[$orderItem->getId()];
            } elseif (!count($qtyList)) {
                $qty = $orderItem->getQtyToRefund();
            } else {
                return (float)$qty;
            }

            if (isset($refundLimits[$orderItem->getId()])) {
                $qty = min($qty, $refundLimits[$orderItem->getId()]);
            }
        }

        return (float)$qty;
    }

    /**
     * Gets shipping amount based on invoice.
     *
     * @param Invoice $invoice
     * @return float
     */
    private function getShippingAmount(Invoice $invoice): float
    {
        $order = $invoice->getOrder();
        $isShippingInclTax = $this->taxConfig->displaySalesShippingInclTax($order->getStoreId());
        if ($isShippingInclTax) {
            $amount = $order->getBaseShippingInclTax() -
                $order->getBaseShippingRefunded() -
                $order->getBaseShippingTaxRefunded();
        } else {
            $amount = $order->getBaseShippingAmount() - $order->getBaseShippingRefunded();
            $amount = min($amount, $invoice->getBaseShippingAmount());
        }

        return (float)$amount;
    }
}
