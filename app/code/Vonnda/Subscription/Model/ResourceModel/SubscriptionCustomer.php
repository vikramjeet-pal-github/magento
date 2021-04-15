<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Model\ResourceModel;

use Vonnda\Subscription\Model\SubscriptionCustomer as SubscriptionCustomerModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SubscriptionCustomer extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('vonnda_subscription_customer', 'id');
    }

    public function setShippingCostOverwriteToNull($id)
    {
        $query = "UPDATE `vonnda_subscription_customer` SET shipping_cost_overwrite = NULL WHERE id=" . $id;
		$this->getConnection()->query($query);
    }
}
