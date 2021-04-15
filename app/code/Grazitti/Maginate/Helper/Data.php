<?php
/**
 * Copyright © 2020 Grazitti . All rights reserved.
 */

namespace Grazitti\Maginate\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    protected $host;
    protected $clientId;
    protected $clientSecret;
    public $filterType; //field to filter off of, required
    public $filterValues; //one or more values for filter, required
    public $fields; //one or more fields to return
    public $batchSize;
    public $input;
    public $nextPageToken;
    public $id;
    protected $_objectManager;
    protected $configWriter;
    public $scopeConfig;
    protected $date;
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\DateTime\DateTime $date
    ) {
        $this->_objectManager = $objectmanager;
        $this->configWriter   = $configWriter;
        $this->scopeConfig = $scopeConfig;
        $this->date = $date;
        parent::__construct($context);
    }
    
    public function checkExpiry()
    {
        $expiry  = $this->scopeConfig->getValue('grazz_auth/graz_settings/expiry_date');
        $curdate = strtotime($this->date->gmtDate('Y-m-d'));
        $exdate  = strtotime($this->date->gmtDate('Y-m-d', $expiry));
        if ($curdate < $exdate) {
            return true;
        } else {
            return false;
        }
    }
    public function canSnowNotification()
    {
        return false;
    }
    
    public function getConfigValue($configdata)
    {
        return $this->scopeConfig->getValue($configdata, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}
