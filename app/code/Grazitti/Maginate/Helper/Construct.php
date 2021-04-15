<?php
/**
 * Copyright © 2020 Grazitti . All rights reserved.
 */

namespace Grazitti\Maginate\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Store\Model\ScopeInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;

class Construct extends AbstractHelper
{
    protected $_objectManager;
    protected $cookieManager;
    protected $cookieMetadataFactory;
    protected $configWriter;
    public $scopeConfig;
    public $_storeManager;
    protected $customerSession;
    public $sessionManager;
    
    public function __construct(
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\SessionFactory $customerSessionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager
    ) {
        $this->_objectManager = $objectmanager;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->configWriter   = $configWriter;
        $this->scopeConfig = $scopeConfig;
        $this->_storeManager=$storeManager;
        $this->customerSession = $customerSession;
        $this->_customerSessionFactory = $customerSessionFactory;
        $this->sessionManager = $sessionManager;
    }
    public function cookieSet($cookie_name, $value, $expire)
    {
        $defaultExpire = 3600;
        $metadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDuration($expire ? $expire : $defaultExpire)
            ->setPath("/");
            //->setDomain($this->sessionManager->getCookieDomain());
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $this->cookieManager->setPublicCookie(
            $cookie_name,
            $value,
            $metadata
        );
    }
    public function getCookie($name)
    {
        return $this->cookieManager->getCookie($name);
    }
    public function getConfigWriter()
    {
        return $this->configWriter;
    }
    public function getScopeConfig()
    {
        return $this->scopeConfig;
    }
    public function getStoreManager()
    {
        return $this->_storeManager;
    }
    public function getCustomerSession()
    {
        return $this->customerSession;
    }
    public function getcustomerFactory()
    {
        return $this->_customerSessionFactory->create();
    }
}
