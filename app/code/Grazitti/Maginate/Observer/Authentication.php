<?php
namespace Grazitti\Maginate\Observer;

use Magento\Customer\Model\Logger;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Grazitti\Maginate\Model\Orderapi;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Authentication implements ObserverInterface
{
    protected $logger;
    protected $_api;
    protected $scopeConfig;
    protected $_leadMeging;
    protected $jsonHelper;
    /**
     * @param Logger $logger
     */
    public function __construct(
        Orderapi $Api,
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Grazitti\Maginate\Helper\Data $dataHelper
    ) {
        $this->logger = $logger;
        $this->_api = $Api;
        $this->scopeConfig = $scopeConfig;
        $this->jsonHelper = $jsonHelper;
        $this->dataHelper = $dataHelper;
    }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
            return $this;
    }
}
