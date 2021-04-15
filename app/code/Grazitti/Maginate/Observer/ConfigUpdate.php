<?php
/**
 * Copyright © 2020 Grazitti. All rights reserved.
 */
namespace Grazitti\Maginate\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class ConfigUpdate implements ObserverInterface
{
    protected $scopeConfig;

    protected $configWriter;

    protected $websiteRepository;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        \Magento\Store\Model\WebsiteRepository $websiteRepository
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->websiteRepository = $websiteRepository;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $request = $observer->getEvent()->getRequest();
        $actionFullName = ($request->getFullActionName());
        if ($actionFullName == 'adminhtml_system_config_edit') {
            if (!$this->isAccountConfirm()) {
                $this->configWriter->save(
                    'grazitti_maginate/general/maginate_lead_integration',
                    0,
                    $scope = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    $scopeId = 0
                );
            } 
        }
    }

    public function isAccountConfirm()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue('customer/create_account/confirm', $storeScope);
    }
}
