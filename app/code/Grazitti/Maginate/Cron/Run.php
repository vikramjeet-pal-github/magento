<?php
/**
 * Copyright © 2018 Graziiti. All rights reserved.
 */
namespace Grazitti\Maginate\Cron;

use Grazitti\Maginate\Model\Orderapi;

class Run extends \Magento\Framework\App\Action\Action
{
    public $_storeManager;
    protected $_objectManager;
    public $scopeConfig;
    protected $date;
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Quote\Model\QueryResolver $queryResolver,
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        Orderapi $Api
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->_objectManager = $objectmanager;
        $this->configWriter = $configWriter;
        $this->_storeManager=$storeManager;
        $this->_api = $Api;
        $this->date = $date;
        parent::__construct($context);
    }
    public function execute()
    {
        
        $key  =   $this->scopeConfig->getValue('grazz_auth/graz_settings/grazz_secret_key');
        $domain = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB, true);
        $response= $this->_api->expiryResponse($key, $domain);
        
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/abandon.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info($response);
        
        $this->configWriter->save('grazz_auth/graz_settings/expiry_date', $response);
        $this->configWriter->save('grazz_auth/graz_settings/expired_date', $response);
        $expiry= $this->scopeConfig->getValue('grazz_auth/graz_settings/expiry_date');
        $curdate=strtotime($this->date->gmtDate('Y-m-d'));
        $exdate=strtotime($this->date->gmtDate('Y-m-d', $expiry));
        $response_time=strtotime($this->date->gmtDate('Y-m-d', $response));
        if ($response == "failure" || $response == "invalid" || $response_time <= $curdate) {
                $this->configWriter->save('grazitti_maginate/orderconfig/maginate_order_integration', '0');
                $this->configWriter->save('grazitti_maginate/dos_prevention/enable_dos', '0');
                $this->configWriter->save('grazitti_maginate/autofill/maginate_autofill_configuration', '0');
                $this->configWriter->save('grazitti_maginate/general/maginate_lead_sync_on_login', '0');
                $this->configWriter->save('grazitti_maginate/abandon_cart/abandon_enable', '0');
        }
        return $this;
    }
}
