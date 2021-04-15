<?php 
/**
 * @copyright: Copyright © 2020 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\GiftOrder\Api;


interface GiftOrderServiceInterface
{
    /**
     * @param string $cartId
     * @param boolean $gift_order
     * @return \Vonnda\GiftOrder\Api\Data\GiftOrderDataInterface
     */
    public function setGiftOrderOnGuestCart(string $cartId, bool $gift_order);

    /**
     * Get current subscription customer and all related info
     * @param boolean $gift_order
     * @return \Vonnda\GiftOrder\Api\Data\GiftOrderDataInterface
     */
    public function setGiftOrderOnCart(bool $gift_order);
}