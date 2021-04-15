<?php
/**
 * Copyright © 2020 Grazitti. All rights reserved.
 */
namespace Grazitti\Maginate\Model\Config\Source;

class Dostime implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'sec', 'label' => __('Seconds')],
            ['value' => 'min', 'label' => __('Minutes')]
        ];
    }
}
