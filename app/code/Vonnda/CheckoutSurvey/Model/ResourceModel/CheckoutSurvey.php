<?php

namespace Vonnda\CheckoutSurvey\Model\ResourceModel;

class CheckoutSurvey extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('vonnda_checkoutsurvey_result', 'entity_id');
    }
}
