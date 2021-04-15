<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Grazitti\Maginate\Model;

use Magento\Framework\Validator\Exception as ValidatorException;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Grazitti\Maginate\Model\Orderapi;

class Apikey extends \Magento\Framework\App\Config\Value
{
    protected $_objectManager;
    protected $configWriter;
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        Orderapi $Api,
        array $data = []
    ) {
        $this->_objectManager = $objectmanager;
        $this->configWriter   = $configWriter;
        $this->_api = $Api;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }
    public function afterSave()
    {
        $munchkinId = $this->getData('fieldset_data')['munchkin_id'];
        $clientid= $this->getData('fieldset_data')['client_id'];
        $secretkey=$this->getData('fieldset_data')['secret_key'];
        $response= $this->_api->checkToken($munchkinId, $clientid, $secretkey);
        $data='';
        if (!$munchkinId) {
            throw new ValidatorException(__('Please enter Munchkin Id first.'));
        } elseif (!$clientid) {
            throw new ValidatorException(__('Please enter Client Id first.'));
        } elseif (!$secretkey) {
            throw new ValidatorException(__('Please enter Secret Key first.'));
        }
        if (!$response) {
            throw new ValidatorException(__('Invalid API Credentials.'));
        }
        return $this;
    }
}
