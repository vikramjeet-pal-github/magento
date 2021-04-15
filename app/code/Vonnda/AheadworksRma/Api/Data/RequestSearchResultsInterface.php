<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\AheadworksRma\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface for request search results
 */
interface RequestSearchResultsInterface extends \Aheadworks\Rma\Api\Data\RequestSearchResultsInterface
{
    /**
     * Get request list
     *
     * @return \Vonnda\AheadworksRma\Api\Data\RequestInterface[]
     */
    public function getItems();

    /**
     * Set request list
     *
     * @param \Vonnda\AheadworksRma\Api\Data\RequestInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
