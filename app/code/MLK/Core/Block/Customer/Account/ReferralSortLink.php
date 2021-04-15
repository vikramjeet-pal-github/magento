<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace MLK\Core\Block\Customer\Account;


use Magento\Customer\Block\Account\SortLink;

/**
 * Class for referral link
 */
class ReferralSortLink extends SortLink
{
    const CONTROLLER_MCA = "mlk_core/customer/referrals";

    const STORE_CODE_US = 'mlk_us_sv';
    
    /**
     * Get current mca
     *
     * @return string
     */
    private function getMca()
    {
        $routeParts = [
            'module' => $this->_request->getModuleName(),
            'controller' => $this->_request->getControllerName(),
            'action' => $this->_request->getActionName(),
        ];

        $parts = [];
        foreach ($routeParts as $key => $value) {
            if (!empty($value) && $value != $this->_defaultPath->getPart($key)) {
                $parts[] = $value;
            }
        }
        return implode('/', $parts);
    }
    
    /**
     * Check if link is specific to controller, as path is overwritten and specified in XML
     *
     * @return bool
     */
    public function isCurrent()
    {
        return $this->getMca() === self::CONTROLLER_MCA;
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if($this->isUSStore()){
            return parent::_toHtml();
        }

        return "";
    }

    protected function isUSStore()
    {
        $isUSStore = $this->_storeManager->getStore()->getCode() === self::STORE_CODE_US;
        return $isUSStore;
    }
}