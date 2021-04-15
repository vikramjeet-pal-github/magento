<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Grazitti\Warranty\Model\Order;

use Vonnda\OrderTag\Model\OrderTag;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\{
    Api\Data\OrderInterface as CoreOrderInterface,
    Api\Data\OrderExtensionInterface,
    Model\Order as CoreSalesOrder
};

class Item extends CoreSalesOrder
{
    
}
