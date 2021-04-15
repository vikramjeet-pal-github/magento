<?php
/*
NOTICE OF LICENSE

This source file is subject to the NekloEULA that is bundled with this package in the file ICENSE.txt.

It is also available through the world-wide-web at this URL: http://store.neklo.com/LICENSE.txt

Copyright (c)  Neklo (http://store.neklo.com/)
*/


namespace Neklo\XTableRate\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    const SALES_TABLERATE_NAME = 'carriers/tablerate/name';
    const SALES_TABLERATE_FREE_NAME = 'carriers/tablerate/free_shipping_name';

    public function getShippingName($websiteId = null)
    {
        return $this->scopeConfig->getValue(self::SALES_TABLERATE_NAME, ScopeInterface::SCOPE_WEBSITE, $websiteId);
    }

    public function getFreeShippingName($websiteId = null)
    {
        return $this->scopeConfig->getValue(self::SALES_TABLERATE_FREE_NAME, ScopeInterface::SCOPE_WEBSITE, $websiteId);
    }
}
