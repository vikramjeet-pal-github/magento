<?php
/**
 * @copyright: Copyright © 2020 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\GiftOrder\Model;

use Magento\Framework\Model\AbstractModel;
use Vonnda\GiftOrder\Api\Data\GiftOrderDataInterface;

class GiftOrderData extends AbstractModel implements GiftOrderDataInterface
{
    const GIFT_ORDER = 'gift_order';

    public function getGiftOrder()
    {
        return $this->_getData(self::GIFT_ORDER);
    }

    public function setGiftOrder($giftOrder)
    {
        $this->setData(self::GIFT_ORDER, $giftOrder);
        return $this;
    }
}