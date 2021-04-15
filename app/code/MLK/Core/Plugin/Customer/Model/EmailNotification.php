<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace MLK\Core\Plugin\Customer\Model;


class EmailNotification
{
    public function aroundNewAccount(\Magento\Customer\Model\EmailNotification $subject, \Closure $proceed)
    {
        return $subject;
    }
}