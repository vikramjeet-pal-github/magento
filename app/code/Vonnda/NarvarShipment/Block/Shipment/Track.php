<?php
namespace Vonnda\NarvarShipment\Block\Shipment;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class Track extends Template
{

    /** @constant string XML_PATH_SHIPPING_ORIGIN_POSTCODE */
    const XML_PATH_SHIPPING_ORIGIN_POSTCODE = 'shipping/origin/postcode';

    /** @property array $template */
    protected $template = "https://molekule.narvar.com/molekule/tracking/%carrier%?tracking_numbers=%trackingNumber%&service=%service%&ozip=%originZipCode%&dzip=%targetZipCode%&order_number='%orderNumber%'&locale=%locale%";

    /** @property array $upsTemplate */
    protected $upsTemplate = "https://www.ups.com/track?loc=%locale%&tracknum=%trackingNumber%&requester=WT/trackdetails";

    /** @property StoreManagerInterface $storeManger */
    protected $storeManager;

    /** @property ScopeConfigInterface $scopeConfig */
    protected $scopeConfig;

    /** @property LoggerInterface $logger */
    protected $logger;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param array $data
     * @return void
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * @param array $variables
     * @return void
     */
    public function insertVariables(array $variables)
    {
        foreach ($variables as $key => $value) {
            $this->template = str_replace($key, $value, $this->template);
        }
    }

    /**
     * @param OrderInterface $order
     * @param \Magento\Sales\Model\Order\Shipment\Track $tracking
     * @return string|null
     */
    public function getTrackingLink(OrderInterface $order, $tracking)
    {
        try {
            if($this->isUPSShipment($tracking)){
                return $this->getUPSTrackingLink($order, $tracking);
            }
            
            $originPostcode = $this->scopeConfig->getValue(self::XML_PATH_SHIPPING_ORIGIN_POSTCODE, ScopeInterface::SCOPE_STORE, $this->storeManager->getStore($order->getStoreId()));
            $storeCode = $this->storeManager->getStore($order->getStoreId())->getCode();
            $carrier = 'fedex'; // this is the only value for US store, and will only change for CA store
            $service = 'ST'; // this is the only value for CA store, and will only change for US store
            if ($storeCode == 'mlk_us_sv') {
                $service = 'FG'; // Ground-Home or Ground-Business Delivery
                $method = $order->getShippingMethod();
                if ($method == 'fedex_FEDEX_2_DAY') {
                    $service = 'E2';
                } elseif ($method == 'fedex_STANDARD_OVERNIGHT') {
                    $service = 'E1';
                } elseif ($method == 'fedex_SMART_POST') {
                    $service = 'SP';
                }
                $locale = 'en_US';
            } elseif ($storeCode == 'mlk_ca_sv') {
                $carrier = strtolower(str_replace(' ', '', $tracking->getTitle()));
                $locale = 'en_CA';
            }
            $shipmentVariables = [
                '%carrier%' => $carrier,
                '%service%' => $service,
                '%trackingNumber%' => $tracking->getTrackNumber() ?: "",
                '%originZipCode%' => $originPostcode ?: "",
                '%targetZipCode%' => $order->getShippingAddress()->getPostcode() ?: "",
                '%orderNumber%' => $order->getIncrementId() ?: "",
                '%locale%' => $locale,
            ];
            $this->insertVariables($shipmentVariables);
            return $this->template;
        } catch (\Exception $e) {
            /* No action required. */
        }
        return null;
    }

    public function isUPSShipment($track)
    {
        if($track->getCarrierCode() == 'ups' 
            || strlen($track->getTrackNumber()) == 18){
            return true;
        }

        return false;
    }

    public function getUPSTrackingLink($order, $track)
    {
        $storeCode = $this->storeManager->getStore($order->getStoreId())->getCode();
        if ($storeCode == 'mlk_us_sv') {
            $locale = 'en_US';
        } elseif ($storeCode == 'mlk_ca_sv') {
            $locale = 'en_CA';
        }
        
        $shipmentVariables = [
            '%trackingNumber%' => $track->getTrackNumber() ?: "",
            '%locale%' => $locale,
        ];

        foreach ($shipmentVariables as $key => $value) {
            $this->upsTemplate = str_replace($key, $value, $this->upsTemplate);
        }
        return $this->upsTemplate;
    }

}