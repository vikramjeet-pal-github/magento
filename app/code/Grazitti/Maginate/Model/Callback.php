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

class Callback extends \Magento\Framework\App\Config\Value
{
    protected $_objectManager;
    public $scopeConfig;
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        Orderapi $Api,
        array $data = []
    ) {
        $this->_objectManager = $objectmanager;
        $this->_api = $Api;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }
    
    public function afterSave()
    {
        $is_orderintegration = $this->getData('fieldset_data')['maginate_order_integration'];
        //Do not check order or product object if order integration not active
        if (!$is_orderintegration) {
                return $this;
        }
        $orderobj=$this->getData('fieldset_data')['ordercustomobject'];
        $productobj=$this->getData('fieldset_data')['productcustomobject'];
        $productobjectresponse= $this->_api->checkObject($productobj);
        $orderobjectresponse= $this->_api->checkObject($orderobj);
        $data='';
        if (!$orderobj) {
            throw new ValidatorException(__('Please Enter Order Custom Object Api Name.'));
        } elseif (!$productobj) {
            throw new ValidatorException(__('Please Enter Product Custom Object Api Name.'));
        }
        $munchkinId = $this->scopeConfig->getValue('grazitti_maginate/email/munchkin_id');
        if ($munchkinId!='') {
            if ($productobjectresponse =='') {
                throw new ValidatorException(__('Invalid Product Custom Object Api Name'));
            } elseif ($orderobjectresponse =='') {
                throw new ValidatorException(__('Invalid Order Custom Object Api Name'));
            }
        }
        return $this;
    }
}
