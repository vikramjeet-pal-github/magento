<?php
namespace Vonnda\AheadworksRma\Model\Shipping;

use Magento\Framework\Module\Dir;

class FedExRmaCarrier
{

    /** @var string */
    protected $_shipServiceWsdl = null;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $scopeConfig;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $_storeManager;

    /** @var \Magento\Framework\Webapi\Soap\ClientFactory */
    protected $soapClientFactory;

    /** @var \Magento\Sales\Api\OrderRepositoryInterface */
    protected $orderRepository;

    /** @var \Vonnda\AheadworksRma\Helper\Locations */
    protected $locations;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Module\Dir\Reader $configReader
     * @param \Magento\Framework\Webapi\Soap\ClientFactory $soapClientFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Vonnda\AheadworksRma\Helper\Locations $locations
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Module\Dir\Reader $configReader,
        \Magento\Framework\Webapi\Soap\ClientFactory $soapClientFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Vonnda\AheadworksRma\Helper\Locations $locations
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $wsdlBasePath = $configReader->getModuleDir(Dir::MODULE_ETC_DIR, 'Magento_Fedex') . '/wsdl/';
        $this->_shipServiceWsdl = $wsdlBasePath . 'ShipService_v10.wsdl';
        $this->soapClientFactory = $soapClientFactory;
        $this->orderRepository = $orderRepository;
        $this->locations = $locations;
    }

    public function requestToShipment($package)
    {
        $order = $this->orderRepository->get($package->getOrderId());
        $requestClient = $this->_formShipmentRequest($package, $order);
        $client = $this->soapClientFactory->create($this->_shipServiceWsdl, ['trace' => 1]);
        if ($this->scopeConfig->isSetFlag('aw_rma/fedex/sandbox_mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $order->getStoreId())) {
            $client->__setLocation('https://wsbeta.fedex.com:443/web-services/');
        } else {
            $client->__setLocation('https://ws.fedex.com:443/web-services/');
        }
        $response = $client->processShipment($requestClient);
        if ($response->HighestSeverity != 'FAILURE' && $response->HighestSeverity != 'ERROR') {
            $data = [
                'tracking_number' => $this->getTrackingNumber($response->CompletedShipmentDetail->CompletedPackageDetails->TrackingIds),
                'label_content' => $response->CompletedShipmentDetail->CompletedPackageDetails->Label->Parts->Image,
            ];
            return $data;
        } else {
            $error = '';
            if (is_array($response->Notifications)) {
                foreach ($response->Notifications as $notification) {
                    $error .= $notification->Message . '; ';
                }
            } else {
                $error = $response->Notifications->Message;
            }
            throw new \Exception($error);
        }
    }

    protected function _formShipmentRequest($package, $order)
    {
        $shippingAddr = $order->getShippingAddress();
        $item = $order->getItemById($package->getItemId());
        $requestClient = [
            'WebAuthenticationDetail' => [
                'UserCredential' => [
                    'Key' => $this->scopeConfig->getValue('aw_rma/fedex/key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $order->getStoreId()),
                    'Password' => $this->scopeConfig->getValue('aw_rma/fedex/password', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $order->getStoreId())
                ],
            ],
            'ClientDetail' => [
                'AccountNumber' => $this->scopeConfig->getValue('aw_rma/fedex/account', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $order->getStoreId()),
                'MeterNumber' => $this->scopeConfig->getValue('aw_rma/fedex/meter_number', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $order->getStoreId())
            ],
            'TransactionDetail' => [
                'CustomerTransactionId' => '*** Express Domestic Shipping Request v9 using PHP ***'
            ],
            'Version' => [
                'ServiceId' => 'ship',
                'Major' => '10',
                'Intermediate' => '0',
                'Minor' => '0'
            ],
            'RequestedShipment' => [
                'ShipTimestamp' => time(),
                'DropoffType' => 'DROP_BOX',
                'PackagingType' => 'YOUR_PACKAGING',
                'ServiceType' => 'FEDEX_GROUND',
                'Shipper' => [
                    'Contact' => [
                        'PersonName' => $shippingAddr->getName(),
                        'CompanyName' => $shippingAddr->getCompany(),
                        'PhoneNumber' => preg_replace('/\D/', '', $shippingAddr->getTelephone())
                    ],
                    'Address' => [
                        'StreetLines' => $shippingAddr->getStreet(),
                        'City' => $shippingAddr->getCity(),
                        'StateOrProvinceCode' => $shippingAddr->getRegionCode(),
                        'PostalCode' => $shippingAddr->getPostcode(),
                        'CountryCode' => $shippingAddr->getCountryId()
                    ],
                ],
                'Recipient' => $this->locations->getRequestLocation($package->getLocation()),
                'ShippingChargesPayment' => [
                    'PaymentType' => 'RECIPIENT',
                    'Payor' => [
                        'AccountNumber' => $this->scopeConfig->getValue('aw_rma/fedex/account', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $order->getStoreId()),
                        'CountryCode' => $this->locations->getLocationData($package->getLocation(), 'country')
                    ],
                ],
                'LabelSpecification' => [
                    'LabelFormatType' => 'COMMON2D',
                    'ImageType' => 'PNG',
                    'LabelStockType' => 'PAPER_8.5X11_TOP_HALF_LABEL'
                ],
                'RateRequestTypes' => ['ACCOUNT'],
                'PackageCount' => 1,
                'RequestedPackageLineItems' => [
                    'SequenceNumber' => '1',
                    'Weight' => [
                        'Units' => 'LB',
                        'Value' => $item->getWeight()
                    ],
                    'CustomerReferences' => [
                        'CustomerReferenceType' => 'CUSTOMER_REFERENCE',
                        'Value' => 'RMA #'.$package->getRequestId(),
                    ],
                    'SpecialServicesRequested' => [
                        'SpecialServiceTypes' => 'SIGNATURE_OPTION',
                        'SignatureOptionDetail' => [
                            'OptionType' => 'NO_SIGNATURE_REQUIRED'
                        ]
                    ]
                ]
            ]
        ];
        return $requestClient;
    }

    /**
     * @param array|object $trackingIds
     * @return string
     */
    private function getTrackingNumber($trackingIds)
    {
        return is_array($trackingIds) ? array_map(function($val) {return $val->TrackingNumber;}, $trackingIds) : $trackingIds->TrackingNumber;
    }

}