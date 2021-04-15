<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */
namespace Vonnda\Subscription\Ui\Component\Listing\Column;

use Vonnda\Subscription\Model\Customer\AddressFactory;
 
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
 
/**
 * Class SubscriptionCustomerAddress
 */
class SubscriptionCustomerAddress extends Column
{
    /**
     * @var PriceCurrencyInterface
     */
    protected $addressFactory;
 
    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param PriceCurrencyInterface $priceFormatter
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        AddressFactory $addressFactory,
        array $components = [],
        array $data = []
    ) {
        $this->addressFactory= $addressFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }
 
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if($item['shipping_address_id']){
                    $idString = " (ID: " . $item['shipping_address_id'] . ")";
                    $item[$this->getData('name')] = $this->buildAddressString($item['shipping_address_id']) . $idString;
                } else {
                    $item[$this->getData('name')] = "Shipping Address unset for this subscription";
                }
            }
        }
 
        return $dataSource;
    }

    protected function buildAddressString($shippingAddressId)
    {
        try {
            $address = $this->addressFactory->create()->load($shippingAddressId);
            $addressString = '';
            $addressString .= $address->getName() . ', ';
            $addressString .= $address->getStreetFull() . ', ';
            $addressString .= $address->getCity() . ', ';
            $addressString .= $address->getRegionCode() . ', ';
            $addressString .= $address->getPostcode() . ', ';
            $addressString .= $address->getCountry();

            return $addressString;
        } catch(\Exception $e){
            return "Address not found";
        }
        
    }
}