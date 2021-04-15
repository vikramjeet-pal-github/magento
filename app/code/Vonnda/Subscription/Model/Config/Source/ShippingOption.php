<?php 

namespace Vonnda\Subscription\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Shipping\Model\Config;
use Magento\Store\Model\StoreManagerInterface;

class ShippingOption implements ArrayInterface
{
    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Shipping\Model\Config
     */
    protected $shippingConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Shipping\Model\Config $shippingConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Config $shippingConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->shippingConfig = $shippingConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Return array of carriers.
     *
     * @param void
     * @return array
     */
    public function toOptionArray()
    {
        $methods = [['value' => '', 'label' => '']];
        $stores = $this->storeManager->getStores();
        foreach($stores as $_store){
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
                $methods[$_carrierCode] = ['label' => $carrierTitle, 'value' => []];
                foreach ($_carrierMethods as $methodCode => $methodTitle) {
                    $methods[$_carrierCode]['value'][] = [
                        'value' => $_carrierCode . '_' . $methodCode,
                        'label' => '[' . $_carrierCode . '] ' . $methodTitle,
                    ];
                }
            }
        }
        return $methods;
    }
}