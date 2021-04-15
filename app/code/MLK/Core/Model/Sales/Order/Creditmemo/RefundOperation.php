<?php
namespace MLK\Core\Model\Sales\Order\Creditmemo;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Creditmemo;

class RefundOperation
{
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
    ) {
        $this->eventManager = $context->getEventDispatcher();
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @param CreditmemoInterface $creditmemo
     * @param OrderInterface $order
     * @param bool $online
     * @return OrderInterface
     */
    public function execute(CreditmemoInterface $creditmemo, OrderInterface $order, $online = false)
    {
        if ($creditmemo->getState() == Creditmemo::STATE_REFUNDED
            && $creditmemo->getOrderId() == $order->getEntityId()
        ) {
            //Builds array of order items to reference later
            $orderItemsMap = [];
            foreach($order->getAllItems() as $_orderItem){
                $orderItemsMap[$_orderItem->getId()] = $_orderItem;
            }

            foreach ($creditmemo->getItems() as $item) {
                if ($item->isDeleted()) {
                    continue;
                }

                // Class overwritten to add this check. When creating a partial refund for an order
                // containing a bundled products, the parent product is getting set as returned even
                // if the child product are not refunded. If an order item has children, then the credit
                // memo item for the bundled product will be returned only if all children are returned. 
                $orderItem = $orderItemsMap[$item->getOrderItemId()];
                if(!empty($orderItem->getChildrenItems())){
                    $allChildProductReturned = true;
                    foreach ($orderItem->getChildrenItems() as $_childOrderItem) {
                        foreach ($creditmemo->getItems() as $_item) {
                            if($_item->getOrderItemId() == $_childOrderItem->getId()){
                                if(($_item->getQty() + $_childOrderItem->getQtyRefunded()) < $item->getQty()){
                                    $allChildProductReturned = false;
                                }
                            }
                        }
                    }
                    if(!$allChildProductReturned){
                        continue;
                    }
                }

                $item->setCreditMemo($creditmemo);
                if ($item->getQty() > 0) {
                    $item->register();
                } else {
                    $item->isDeleted(true);
                }
            }

            $baseOrderRefund = $this->priceCurrency->round(
                $order->getBaseTotalRefunded() + $creditmemo->getBaseGrandTotal()
            );
            $orderRefund = $this->priceCurrency->round(
                $order->getTotalRefunded() + $creditmemo->getGrandTotal()
            );
            $order->setBaseTotalRefunded($baseOrderRefund);
            $order->setTotalRefunded($orderRefund);

            $order->setBaseSubtotalRefunded($order->getBaseSubtotalRefunded() + $creditmemo->getBaseSubtotal());
            $order->setSubtotalRefunded($order->getSubtotalRefunded() + $creditmemo->getSubtotal());

            $order->setBaseTaxRefunded($order->getBaseTaxRefunded() + $creditmemo->getBaseTaxAmount());
            $order->setTaxRefunded($order->getTaxRefunded() + $creditmemo->getTaxAmount());
            $order->setBaseDiscountTaxCompensationRefunded(
                $order->getBaseDiscountTaxCompensationRefunded() + $creditmemo->getBaseDiscountTaxCompensationAmount()
            );
            $order->setDiscountTaxCompensationRefunded(
                $order->getDiscountTaxCompensationRefunded() + $creditmemo->getDiscountTaxCompensationAmount()
            );

            $order->setBaseShippingRefunded($order->getBaseShippingRefunded() + $creditmemo->getBaseShippingAmount());
            $order->setShippingRefunded($order->getShippingRefunded() + $creditmemo->getShippingAmount());

            $order->setBaseShippingTaxRefunded(
                $order->getBaseShippingTaxRefunded() + $creditmemo->getBaseShippingTaxAmount()
            );
            $order->setShippingTaxRefunded($order->getShippingTaxRefunded() + $creditmemo->getShippingTaxAmount());

            $order->setAdjustmentPositive($order->getAdjustmentPositive() + $creditmemo->getAdjustmentPositive());
            $order->setBaseAdjustmentPositive(
                $order->getBaseAdjustmentPositive() + $creditmemo->getBaseAdjustmentPositive()
            );

            $order->setAdjustmentNegative($order->getAdjustmentNegative() + $creditmemo->getAdjustmentNegative());
            $order->setBaseAdjustmentNegative(
                $order->getBaseAdjustmentNegative() + $creditmemo->getBaseAdjustmentNegative()
            );

            $order->setDiscountRefunded($order->getDiscountRefunded() + $creditmemo->getDiscountAmount());
            $order->setBaseDiscountRefunded($order->getBaseDiscountRefunded() + $creditmemo->getBaseDiscountAmount());

            if ($online) {
                $order->setTotalOnlineRefunded($order->getTotalOnlineRefunded() + $creditmemo->getGrandTotal());
                $order->setBaseTotalOnlineRefunded(
                    $order->getBaseTotalOnlineRefunded() + $creditmemo->getBaseGrandTotal()
                );
            } else {
                $order->setTotalOfflineRefunded($order->getTotalOfflineRefunded() + $creditmemo->getGrandTotal());
                $order->setBaseTotalOfflineRefunded(
                    $order->getBaseTotalOfflineRefunded() + $creditmemo->getBaseGrandTotal()
                );
            }

            $order->setBaseTotalInvoicedCost(
                $order->getBaseTotalInvoicedCost() - $creditmemo->getBaseCost()
            );

            $creditmemo->setDoTransaction(!$creditmemo->getPaymentRefundDisallowed() && $online);
            $order->getPayment()->refund($creditmemo);

            $this->eventManager->dispatch('sales_order_creditmemo_refund', ['creditmemo' => $creditmemo]);
        }

        return $order;
    }
}
