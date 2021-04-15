<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MLK\Core\Api\Catalog\Data;

/**
 * Dto that holds render information about products
 */
interface ProductRenderSearchResultsInterface
{
    /**
     * Get list of products rendered information
     *
     * @return \MLK\Core\Api\Catalog\Data\ProductRenderInterface[]
     */
    public function getItems();

    /**
     * Set list of products rendered information
     *
     * @api
     * @param \MLK\Core\Api\Catalog\Data\ProductRenderInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
