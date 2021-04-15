<?php

namespace Potato\Zendesk\Controller\Sso;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Potato\Zendesk\Model\Config;
use Potato\Zendesk\Api\SsoManagementInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;

class Check extends Action
{
    /** @var Config  */
    protected $config;

    /** @var SsoManagementInterface  */
    protected $ssoManagementInterface;

    /** @var CustomerSession  */
    protected $customerSession;

    /** @var CookieManagerInterface  */
    protected $cookieManager;

    /** @var CookieMetadataFactory  */
    protected $cookieMetadataFactory;

    /**
     * @param Context $context
     * @param Config $config
     * @param SsoManagementInterface $ssoManagementInterface
     * @param CustomerSession $customerSession
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     */
    public function __construct(
        Context $context,
        Config $config,
        SsoManagementInterface $ssoManagementInterface,
        CustomerSession $customerSession,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory
    ) {
        parent::__construct($context);
        $this->ssoManagementInterface = $ssoManagementInterface;
        $this->config = $config;
        $this->customerSession = $customerSession;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $customerId = $this->customerSession->getCustomerId();

        $result = [];
        if (!$this->config->isSsoEnabled()) {
            return $resultJson->setData($result);
        }

        $cookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setDomain($this->customerSession->getCookieDomain())
            ->setPath($this->customerSession->getCookiePath());

        $returnLogoutUrl = $this->getReturnLogoutUrl();
        if ($returnLogoutUrl) {
            $result['return_to'] = $returnLogoutUrl;
            try {
                $this->cookieManager->deleteCookie(Config::SSO_LOGOUT_RETURN_COOKIE_NAME, $cookieMetadata);
            } catch (\Exception $e) {
            }
            return $resultJson->setData($result);
        }

        if (!$customerId || null !== $this->cookieManager->getCookie(Config::SSO_COOKIE_NAME)) {
            return $resultJson->setData($result);
        }

        $location = $this->ssoManagementInterface->getLocationByCustomer($this->customerSession->getCustomer());

        $returnLoginUrl = $this->getReturnLoginUrl();
        if ($returnLoginUrl) {
            $result['return_to'] = $location . $returnLoginUrl;
        } else {
            $result['iframe'] = "<iframe src='{$location}' height='1' width='1'></iframe>";
        }

        try {
            $this->cookieManager->setPublicCookie(Config::SSO_COOKIE_NAME, true, $cookieMetadata);
            $this->cookieManager->deleteCookie(Config::SSO_RETURN_COOKIE_NAME, $cookieMetadata);
        } catch (\Exception $e) {
        }

        return $resultJson->setData($result);
    }

    /**
     * @return null|string
     */
    private function getReturnLoginUrl()
    {
        $result = null;
        $returnUrl = $this->cookieManager->getCookie(Config::SSO_RETURN_COOKIE_NAME);
        if (!$this->config->isSsoReturnEnabled() || !$returnUrl) {
            return $result;
        }
        $result = "&return_to=" . urlencode($returnUrl);
        return $result;
    }

    /**
     * @return null|string
     */
    private function getReturnLogoutUrl()
    {
        $result = null;
        if (!$this->config->isSsoReturnEnabled() || !$this->cookieManager->getCookie(Config::SSO_LOGOUT_RETURN_COOKIE_NAME)) {
            return $result;
        }
        return $this->cookieManager->getCookie(Config::SSO_LOGOUT_RETURN_COOKIE_NAME);
    }
}