<?php

namespace Vonnda\CheckoutSurvey\Model\ResourceModel\CheckoutSurvey;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Vonnda\CheckoutSurvey\Model\CheckoutSurvey::class,
            \Vonnda\CheckoutSurvey\Model\ResourceModel\CheckoutSurvey::class
        );
    }
}
