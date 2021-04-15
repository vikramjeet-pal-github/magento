<?php
/**
 * Copyright © 2020 Grazitti . All rights reserved.
 */
namespace Grazitti\Maginate\Block\Adminhtml\System;

class Config extends \Magento\Framework\View\Element\Template
{
    protected $scopeConfig;
    protected $configWriter;
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter   = $configWriter;
        parent :: __construct($context, $data);
    }

    public function isAccountConfirm()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue('customer/create_account/confirm', $storeScope);
    }
}
