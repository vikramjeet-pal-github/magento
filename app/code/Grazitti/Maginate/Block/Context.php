<?php
/**
 * Copyright © 2020 Graziiti. All rights reserved.
 */
namespace Grazitti\Maginate\Block;

/**
 * Abstract product block context
 */
 
class Context extends \Magento\Framework\View\Element\Template\Context
{
    
    protected $_config;
    
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;
    
    /**
     * @var \Magento\Framework\UrlFactory
     */
    public function __construct(
        \Grazitti\Maginate\Model\Config $config,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        
        $this->_config = $config;
        $this->_objectManager=$objectManager;
        
        parent::__construct();
    }
    /**
     * Function for getting maginate model config object
     * @return \Grazitti\Maginate\Model\Config
     */
    public function getConfig()
    {
        return $this->_config;
    }
    /**
     * Function for getting object manager object
     * @return \Magento\Framework\ObjectManagerInterface
     */
    public function getObjectManager()
    {
        return $this->_objectManager;
    }
}
