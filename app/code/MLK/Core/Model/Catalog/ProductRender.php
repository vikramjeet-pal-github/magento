<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MLK\Core\Model\Catalog;

use Magento\Catalog\Api\Data\ProductRender\ButtonInterface;
use Magento\Catalog\Api\Data\ProductRender\PriceInfoInterface;
use MLK\Core\Api\Catalog\Data\ProductRenderInterface;

/**
 * DTO which represents structure for product render information
 */
class ProductRender extends \Magento\Catalog\Model\ProductRender implements ProductRenderInterface
{
    protected $_links = [];
    /**
     * Retrieve sku through type instance
     *
     * @return string
     */
    public function getSku()
    {
        return $this->getData('sku');
    }

    /**
     * Set product sku
     *
     * @param string $sku
     * @return $this
     */
    public function setSku($sku)
    {
        return $this->setData('sku', $sku);
    }

    /**
     * Get all bundle product options of product
     *
     * @return \Magento\Bundle\Api\Data\OptionInterface[]|null
     */
    public function getBundleProductOptions()
    {
        return $this->getData('bundle_product_options');
    }

    /**
     * Set all bundle product options of product
     *
     * @param \Magento\Bundle\Api\Data\OptionInterface[] $bundleProductOptions
     * @return $this
     */
    public function setBundleProductOptions(array $bundleProductOptions = null)
    {
        $this->setData('bundle_product_options', $bundleProductOptions);
        return $this;
    }
}
