<?php
/**
 * @copyright: Copyright © 2020 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\GiftOrder\Api\Data;

interface GiftOrderDataInterface
{
    /**
     * @param boolean $giftOrder
     * @return $this
     */
    public function setGiftOrder($giftOrder);

    /**
     * @return boolean
     */
    public function getGiftOrder();
}