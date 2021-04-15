<?php

namespace Vonnda\CheckoutSurvey\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_VONNDA_CHECKOUT_SURVEY = 'checkout/checkoutsurvey/';

    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getQuestion($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_VONNDA_CHECKOUT_SURVEY . "question", $storeId);
    }

    public function getAnswerOptions($storeId = null)
    {
        return explode(",", $this->getConfigValue(
            self::XML_PATH_VONNDA_CHECKOUT_SURVEY . "answer_options",
            $storeId
        ));
    }

    public function isRandomizeAnswerOptions($storeId = null)
    {
        return (bool) $this->getConfigValue(
            self::XML_PATH_VONNDA_CHECKOUT_SURVEY . "randomize_answer_options",
            $storeId
        );
    }

    public function isEnabled($storeId = null)
    {
        return (bool) $this->getConfigValue(self::XML_PATH_VONNDA_CHECKOUT_SURVEY . "enable", $storeId);
    }

}