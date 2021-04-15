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

class AbandonCallback extends \Magento\Framework\App\Config\Value
{
    protected $_objectManager;
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectmanager,
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
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }
    public function afterSave()
    {
        $abandonobj= $this->getData('fieldset_data')['abandon_cart_custom_object_api_name'];
        $abandonobjectresponse= $this->_api->checkObject($abandonobj);
        
        $data='';
        if (!$abandonobj) {
            throw new ValidatorException(__('Please Enter Abandon Cart Custom Object Api Name.'));
        }
        if ($abandonobjectresponse =='') {
            throw new ValidatorException(__('Invalid Abandon Cart Custom Object Api Name'));
        }
        return $this;
    }
}
