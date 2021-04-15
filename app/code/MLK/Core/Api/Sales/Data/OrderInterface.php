<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace MLK\Core\Api\Sales\Data;

use Magento\Sales\{
    Api\Data\OrderInterface as CoreOrderInterface,
    Api\Data\OrderExtensionInterface
};

interface OrderInterface extends CoreOrderInterface
{
    /**
     * Parent Order Id
     *
     * @api
     * @param void
     * @return int
     */
    public function getParentOrderId();

    /**
     * Order Tag
     *
     * @api
     * @param void
     * @return string
     */
    public function getOrderTag();

    /**
     * Set Parent Order Id
     * @api
     * @param int $parentOrderId
     * @return $this
     */
    public function setParentOrderId($parentOrderId);

    /**
     * @api
     * @return bool
     */
    public function getSignatureRequired(): bool;

    /**
     * @api
     * @param bool $signatureRequired
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function setSignatureRequired(bool $signatureRequired): CoreOrderInterface;

    /**
     * @api
     * @return bool
     */
    public function getGiftOrder(): bool;

    /**
     * @api
     * @param mixed $giftOrder
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function setGiftOrder($giftOrder): CoreOrderInterface;

    /**
     * @api
     * @return \Magento\Sales\Api\Data\OrderExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * @api
     * @param \Magento\Sales\Api\Data\OrderExtensionInterface $extensionAttributes
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function setExtensionAttributes(OrderExtensionInterface $extensionAttributes);
}
