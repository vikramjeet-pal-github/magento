<?php

namespace Vonnda\AlternativeOrigin\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_STORE_ENABLED = 'shipping/alternative_origin/enabled';

    const XML_PATH_STORE_ADDRESS1 = 'shipping/alternative_origin/street_line1';

    const XML_PATH_STORE_ADDRESS2 = 'shipping/alternative_origin/street_line2';

    const XML_PATH_STORE_CITY = 'shipping/alternative_origin/city';

    const XML_PATH_STORE_REGION_ID = 'shipping/alternative_origin/region_id';

    const XML_PATH_STORE_ZIP = 'shipping/alternative_origin/postcode';

    const XML_PATH_STORE_COUNTRY_ID = 'shipping/alternative_origin/country_id';

    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue($field, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function isEnabled($storeId = null)
    {
        $isEnabled = $this->getConfigValue(self::XML_PATH_STORE_ENABLED, $storeId);
        return ($isEnabled != null && $isEnabled == 1) ? true : false;
    }
}
