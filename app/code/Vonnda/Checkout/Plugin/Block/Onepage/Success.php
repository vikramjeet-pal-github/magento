<?php
namespace Vonnda\Checkout\Plugin\Block\Onepage;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Success
{

    protected $customerSession;
    protected $scopeConfig;

    public function __construct(
        Session $customerSession,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->customerSession = $customerSession;
        $this->scopeConfig = $scopeConfig;
    }

    public function afterToHtml(\Vonnda\Checkout\Block\Onepage\Success $subject, $result)
    {
        if ($this->scopeConfig->getValue('aw_osc/general/auto_logout', ScopeInterface::SCOPE_STORE)) {
            if ($this->customerSession->isLoggedIn()) {
                $this->customerSession->logout();
            }
            $this->customerSession->destroy(['clear_storage' => true, 'send_expire_cookie' => true]);
        }
        return $result;
    }

}