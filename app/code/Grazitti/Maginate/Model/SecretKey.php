<?php
/**
 * Copyright © 2020 Grazitti. All rights reserved.
 */
namespace Grazitti\Maginate\Model;

use Magento\Framework\Validator\Exception as ValidatorException;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Grazitti\Maginate\Model\Orderapi;

class SecretKey extends \Magento\Framework\App\Config\Value
{
    public $scopeConfig;
    public $_storeManager;
    protected $_objectManager;
    protected $configWriter;
    protected $date;
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        Orderapi $Api,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter   = $configWriter;
        $this->_storeManager=$storeManager;
        $this->_objectManager = $objectmanager;
        $this->_api = $Api;
        $this->date = $date;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }
    public function afterSave()
    {
        $grazittiSceretKey=$this->getData('fieldset_data')['grazz_secret_key'];
        $data='';
        $domain = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB, true);
        $response= $this->_api->expiryResponse($grazittiSceretKey, $domain);
        $this->configWriter->save('grazz_auth/graz_settings/expiry_date', $response);
        $this->configWriter->save('grazz_auth/graz_settings/expired_date', $response);
        $exipry_date = $this->scopeConfig->getValue('grazz_auth/graz_settings/expiry_date');
        if (!$grazittiSceretKey) {
            throw new ValidatorException(__('Please Enter Grazitti Secret Key.'));
        }
        if ($response =='failure') {
                throw new ValidatorException(__('Invalid Secret key.'));
        }
        $curdate=strtotime($this->date->gmtDate('Y-m-d'));
        $response_time=strtotime($this->date->gmtDate('Y-m-d', $response));
        if ($response == "failure" || $response == "invalid" || $response_time <= $curdate) {
                $this->configWriter->save('grazitti_maginate/orderconfig/maginate_order_integration', '0');
                $this->configWriter->save('grazitti_maginate/dos_prevention/enable_dos', '0');
                $this->configWriter->save('grazitti_maginate/general/maginate_lead_sync_on_login', '0');
                $this->configWriter->save('grazitti_maginate/autofill/maginate_autofill_configuration', '0');
                $this->configWriter->save('grazitti_maginate/abandon_cart/abandon_enable', '0');
        }
        return $this;
    }
}
