<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\AheadworksRma\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Request item interface
 */
interface RequestItemInterface extends \Aheadworks\Rma\Api\Data\RequestItemInterface
{    
    const SKU = 'sku';
    /**
     * Get Sku
     *
     * @return string|null
     */
    public function getItemSku();

    /**
     * Get Location
     *
     * @return string|null
     */
    public function getLocation();



}
