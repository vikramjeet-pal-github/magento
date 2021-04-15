<?php
/**
 * Copyright © 2020 Grazitti. All rights reserved.
 */
namespace Grazitti\Maginate\Observer\Adminhtml;

class LayoutHandler implements \Magento\Framework\Event\ObserverInterface
{
    public function __construct(\Magento\Framework\App\RequestInterface $request)
    {
        $this->request = $request;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $params = $this->request->getParams();

        if (! empty($params['section'])) {
            $moduleName = $this->getExpiry();
            if ($params['section'] == "grazz_auth") {
                $layout = $observer->getData('layout');
                $layout->getUpdate()->addHandle('adminhtml_system_config_edit_section_custom_handler');
            }
        }
    }

    private function getExpiry()
    {
        $class = get_class($this);
        $moduleName = strtolower(
            str_replace('\\', '_', substr($class, 0, strpos($class, '\\Observer')))
        );
        return (string) $moduleName;
    }
}
