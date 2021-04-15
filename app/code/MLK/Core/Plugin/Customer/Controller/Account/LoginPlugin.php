<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace MLK\Core\Plugin\Customer\Controller\Account;

use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Store\Model\StoreManagerInterface;

class LoginPlugin
{
    protected $session;

    protected $cookieManager;

    protected $cookieMetadataFactory;

    protected $accountRedirect;

    protected $storeManager;


    public function __construct(
        Session $session,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        AccountRedirect $accountRedirect,
        StoreManagerInterface $storeManager
    ) {
        $this->session = $session;
        $this->cookieManager = $cookieManager;
        $this->accountRedirect = $accountRedirect;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->storeManager = $storeManager;
    }


    /**
     * Change redirect after login to autorefill
     *
     * @param \Magento\Customer\Controller\Account\LoginPost $subject
     * @param \Magento\Framework\Controller\Result\Redirect $result
     */
    public function afterExecute(
        \Magento\Customer\Controller\Account\Login $subject,
        $result
    ) {
        $route = $this->session->getAutoRefillRedirectRoute();
        if($route){
            $metadata = $this->cookieMetadataFactory
                ->createPublicCookieMetadata()
                ->setPath("/");
            $store = $this->storeManager->getStore();
            $baseUrl = $store->getBaseUrl();
            $this->cookieManager->setPublicCookie(AccountRedirect::LOGIN_REDIRECT_URL, $baseUrl . $route, $metadata);
        }

        return $result;
    }
}
