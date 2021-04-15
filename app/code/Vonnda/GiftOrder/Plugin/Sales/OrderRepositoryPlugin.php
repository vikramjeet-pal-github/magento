<?php
namespace Vonnda\GiftOrder\Plugin\Sales;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderAddressExtensionFactory;

/**
 * Class OrderRepositoryPlugin
 */
class OrderRepositoryPlugin
{
    /**
     * @var OrderExtensionFactory
     */
    private $extensionFactory;
    /**
     * @var OrderAddressExtensionFactory
     */
    private $addressExtensionFactory;

    /**
     * OrderRepositoryPlugin constructor.
     *
     * @param OrderExtensionFactory $orderExtensionFactory
     * @param OrderAddressExtensionFactory $orderAddressExtensionFactory     */
    public function __construct(
        OrderExtensionFactory $orderExtensionFactory,
        OrderAddressExtensionFactory $orderAddressExtensionFactory   
        ) {
        $this->extensionFactory = $orderExtensionFactory;
        $this->orderAddressExtensionFactory = $orderAddressExtensionFactory;    
    }

    /**
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $result
     * @return array
     */
    public function beforeSave(
        OrderRepositoryInterface $subject,
        OrderInterface $order
    ) {
        $extensionAttributes = $order->getExtensionAttributes() ?: $this->extensionFactory->create();
        if ($extensionAttributes !== null && $extensionAttributes->getGiftOrder() !== null) {
            $order->setGiftOrder($extensionAttributes->getGiftOrder());
        }

        if (!$order->getIsVirtual()) {
            $extensionAttributes = $order->getShippingAddress()->getExtensionAttributes() ?: $this->extensionFactory->create();
            if ($extensionAttributes !== null && $extensionAttributes->getGiftRecipientEmail() !== null) {
                $order->getShippingAddress()->setGiftRecipientEmail($extensionAttributes->getGiftRecipientEmail());
            }
        }

        return [$order];
    }
}