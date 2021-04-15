<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\AheadworksRma\Api\Data;

/**
 * Request interface
 */
interface RequestInterface extends \Aheadworks\Rma\Api\Data\RequestInterface
{
    /**
     * Get order items
     *
     * @return \Vonnda\AheadworksRma\Api\Data\RequestItemInterface[]
     */
    public function getOrderItems();

}
