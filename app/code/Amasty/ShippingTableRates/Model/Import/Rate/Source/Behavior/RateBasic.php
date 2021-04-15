<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_ShippingTableRates
 */


namespace Amasty\ShippingTableRates\Model\Import\Rate\Source\Behavior;

use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Source\Import\AbstractBehavior;

class RateBasic extends AbstractBehavior
{
    /**
     * @return array
     */
    public function toArray()
    {
        return [
            Import::BEHAVIOR_CUSTOM => __('Add')
        ];
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return 'amastratebasic';
    }
}
