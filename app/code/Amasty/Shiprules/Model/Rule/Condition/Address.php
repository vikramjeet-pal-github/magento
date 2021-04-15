<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Model\Rule\Condition;

/**
 * Address Conditions.
 */
class Address extends \Amasty\CommonRules\Model\Rule\Condition\Address
{
    /**
     * @return $this
     */
    public function loadAttributeOptions()
    {
        $attributes = [
            'package_value_with_discount' => __('Subtotal'),
            'package_qty' => __('Total Items Quantity'),
            'package_weight' => __('Total Weight'),
            'dest_postcode' => __('Shipping Postcode'),
            'dest_region_id' => __('Shipping State/Province'),
            'dest_country_id' => __('Shipping Country'),
            'dest_city' => __('Shipping City'),
            'dest_street' => __('Shipping Address Line'),
        ];

        $this->setAttributeOption($attributes);

        return $this;
    }
}
