<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\ViewModel;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Backend\Model\Session\Proxy as Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Shipping\Model\Config as ShippingConfig;
use Magento\Store\Model\StoreManagerInterface;

class BulkAssignShippingMethodOverwrite implements ArgumentInterface
{
    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Backend\Model\Session\Proxy
     */
    protected $session;

    /**
     * @var \Magento\Shipping\Model\Config
     */
    protected $shippingConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param SourceRepositoryInterface $sourceRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Session $session,
        ScopeConfigInterface $scopeConfig,
        ShippingConfig $shippingConfig,
        StoreManagerInterface $storeManager

    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->session = $session;
        $this->scopeConfig = $scopeConfig;
        $this->shippingConfig = $shippingConfig;
        $this->storeManager = $storeManager;
    }

    public function getSubscriptionCount()
    {
        return count($this->session->getSubscriptionCustomerIds());
    }

    public function getShippingOptionsArray()
    {
        $methods = [['value' => '', 'label' => '']];
        $stores = $this->storeManager->getStores();
        foreach($stores as $_store){
            $isStore = (int)$_store->getId() === (int)$this->storeManager->getStore()->getId();
            if($isStore){
                $_carriers = $this->shippingConfig->getAllCarriers($_store->getId());
                foreach ($_carriers as $_carrierCode => $_carrierModel) {
                    if (!$_carrierModel->isActive()) {
                        continue;
                    }
                    $_carrierMethods = $_carrierModel->getAllowedMethods();
                    if (!$_carrierMethods) {
                        continue;
                    }
                    $carrierTitle = $this->scopeConfig->getValue(
                        'carriers/' . $_carrierCode . '/title',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    );
                    foreach ($_carrierMethods as $methodCode => $methodTitle) {
                        $methods[] = [
                            'value' => $_carrierCode . '_' . $methodCode,
                            'label' => $carrierTitle . " - " . $methodTitle,
                        ];
                    }
                }
            }
        }
        return $methods;
    }

}
