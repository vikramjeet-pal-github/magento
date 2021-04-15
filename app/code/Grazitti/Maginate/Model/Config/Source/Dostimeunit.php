<?php
/**
 * Copyright © 2020 Grazitti. All rights reserved.
 */
namespace Grazitti\Maginate\Model\Config\Source;

class Dostimeunit implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        for ($i=1; $i<60; $i++) {
            $options[] = ['value' => $i, 'label' => __($i)];
        }
        return $options;
    }
}
