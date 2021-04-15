<?php

namespace MLK\Core\Api\Catalog\Data;

/**
 * Represents Data Object which holds enough information to render product
 * This information is put into part as Add To Cart or Add to Compare Data or Price Data
 *
 * @api
 * @since 102.0.0
 */
interface ProductRenderInterface extends \Magento\Catalog\Api\Data\ProductRenderInterface
{
    /**
     * Product sku
     *
     * @return string
     */
    public function getSku();

    /**
     * Set product sku
     *
     * @param string $sku
     * @return $this
     */
    public function setSku($sku);

    /**
     * Get all bundle product options of product
     *
     * @return \Magento\Bundle\Api\Data\OptionInterface[]|null
     */
    public function getBundleProductOptions();

    /**
     * Set all bundle product options of product
     *
     * @param \Magento\Bundle\Api\Data\OptionInterface[] $options
     * @return $this
     */
    public function setBundleProductOptions(array $options = null);
}
