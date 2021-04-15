<?php
/**
 * Associated products collection
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vonnda\Subscription\Model\ResourceModel\SubscriptionPlan;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductCollection extends \Magento\Catalog\Model\ResourceModel\Product\Collection
{
    /**
     * @inheritdoc
     */
    public function _initSelect()
    {
        parent::_initSelect();
        $this->addAttributeToSelect(
            'name'
        )->addAttributeToSelect(
            'price'
        )->addAttributeToSelect(
            'sku'
        );

        return $this;
    }
}