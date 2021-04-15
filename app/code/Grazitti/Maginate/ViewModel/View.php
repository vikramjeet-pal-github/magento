<?php
/**
 * Copyright © 2020 Grazitti. All rights reserved.
 */
namespace Grazitti\Maginate\ViewModel;

class View implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    private $scopeHelper;
    private $helperData;
    public function __construct(
        \Grazitti\Maginate\Helper\Construct $scopeHelper,
        \Grazitti\Maginate\Helper\Data $helperData
    ) {
        $this->scopeHelper = $scopeHelper;
        $this->helperData = $helperData;
    }
    public function getConfigValue($key)
    {
        return $this->scopeHelper->getScopeConfig()->getValue($key);
    }
    public function getScopeHelper()
    {
        return $this->scopeHelper;
    }
    public function getHelper()
    {
        return $this->helperData;
    }
}
