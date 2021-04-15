<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Model\Customer;

use Magento\Customer\Model\Address as CoreAddress;

class Address extends CoreAddress
{
    public function setParentId($parentId)
    {
        if($parentId === 0){
            $this->setData('parent_id', null);
            return $this;
        } else {
            parent::setParentId($parentId);
            return $this;
        }

    }
}