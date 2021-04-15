<?php
namespace Potato\Zendesk\Block;

use Magento\Framework\View\Element\Template;
use Potato\Zendesk\Model\Config;

class Sso extends Template
{
    /**
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('po_zendesk/sso/check');
    }

    /**
     * @return string
     */
    public function getCookieName()
    {
        return Config::SSO_COOKIE_NAME;
    }
}
