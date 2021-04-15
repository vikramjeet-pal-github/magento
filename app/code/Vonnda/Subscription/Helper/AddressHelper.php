<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Customer\Api\Data\RegionInterface;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Customer\Model\AddressRegistry;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Address Helper
 *
 * Because the default functionality is too intertwined with customerId
 * 
 */
class AddressHelper extends AbstractHelper
{
    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     */
    protected $addressRepository;

    /**
     * @var \Magento\Customer\Model\AddressFactory
     */
    protected $addressFactory;


    /**
     * @var \Magento\Customer\Api\Data\AddressInterfaceFactory $addressInterfaceFactory
     */
    protected $addressInterfaceFactory;

    /**
     * @var RegionFactory
     */
    protected $regionFactory;

    /**
     * @var RegionDataFactory
     */
    protected $regionDataFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var AddressRegistry
     */
    protected $addressRegistry;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManagerInterface;

    public function __construct(
        AddressRepositoryInterface $addressRepository,
        AddressInterfaceFactory $addressInterfaceFactory,
        RegionFactory $regionFactory,
        RegionInterfaceFactory $regionDataFactory,
        DataObjectHelper $dataObjectHelper,
        AddressFactory $addressFactory,
        AddressRegistry $addressRegistry,
        StoreManagerInterface $storeManagerInterface
    ) {
        $this->addressRepository = $addressRepository;
        $this->addressFactory = $addressFactory;
        $this->addressInterfaceFactory = $addressInterfaceFactory;
        $this->regionFactory = $regionFactory;
        $this->regionDataFactory = $regionDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->addressRegistry = $addressRegistry;
        $this->storeManagerInterface = $storeManagerInterface;
    }

    /**
     * Create an address from a data object
     *
     * @param array $addressData
     * @return 
     * 
     */
    public function createAddressInterfaceFromData(array $addressData)
    {
        $street = false;
        if(!is_array($addressData['street'])){
            $street = [$addressData['street']];
        }
        $addressDataObject = $this->addressInterfaceFactory->create();
        $addressDataObject->setRegionId($addressData['region_id'])
                          ->setRegion($this->getRegionInterface($addressData['region_id']))
                          ->setCustomerId(isset($addressData['customer_id']) ? $addressData['customer_id'] : null)
                          ->setCity($addressData['city'])
                          ->setPostcode($addressData['postcode'])
                          ->setFirstname($addressData['firstname'])
                          ->setLastname($addressData['lastname'])
                          ->setTelephone($addressData['telephone'])
                          ->setStreet($street ? $street : $addressData['street'])
                          ->setCountryId($addressData['country_id']);

        if(isset($addressData['entity_id']) && $addressData['entity_id'] != null){
            $addressDataObject->setId($addressData['entity_id']);
        }
        return $addressDataObject;
    }

    /**
     * Get address interface - oddly without doing it this way it was throwing an error
     *
     * @param array $addressData
     * @return 
     * 
     */
    public function getAddressInterfaceObject($shippingAddress)
    {
        $street = false;
        if(!is_array($shippingAddress->getStreet())){
            $street = [$shippingAddress->getStreet()];
        }
        $addressDataObject = $this->addressInterfaceFactory->create();
        $addressDataObject->setRegionId($shippingAddress->getRegionId())
                          ->setRegion($this->getRegionInterface($shippingAddress->getRegionId()))
                          ->setCustomerId(null)
                          ->setCompany($shippingAddress->getCompany())
                          ->setCity($shippingAddress->getCity())
                          ->setPostcode($shippingAddress->getPostCode())
                          ->setFirstname($shippingAddress->getFirstname())
                          ->setLastname($shippingAddress->getLastName())
                          ->setTelephone($shippingAddress->getTelephone())
                          ->setStreet($street ? $street : $shippingAddress->getStreet())
                          ->setCountryId($shippingAddress->getCountryId())
                          ->setIsDefaultBilling(false)
                          ->setIsDefaultShipping(false);

        return $addressDataObject;
    }

    /**
     * Get region data
     *
     * @param int $regionId
     * @return \Magento\Customer\Api\Data\RegionInterface $region
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getRegionInterface($regionId)
    {
        $regionCode = '';
        $regionName = '';
        if ($regionId) {
            $newRegion = $this->regionFactory->create()->load($regionId);
            $regionCode = $newRegion->getCode();
            $regionName = $newRegion->getDefaultName();
        }

        $regionData = [
            RegionInterface::REGION_ID => $regionId ? $regionId : null,
            RegionInterface::REGION => $regionName ? $regionName : null,
            RegionInterface::REGION_CODE => $regionCode
                ? $regionCode
                : null,
        ];

        $region = $this->regionDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $region,
            $regionData,
            \Magento\Customer\Api\Data\RegionInterface::class
        );
        return $region;
    }

    /**
     * Create a new address - core address creation requires a customer
     *
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return \Magento\Customer\Model\Address $addressModel
     * 
     */
    public function createNewAddress(\Magento\Customer\Api\Data\AddressInterface $address)
    {
        /** @var \Magento\Customer\Model\Address $addressModel */
        $addressModel = $this->addressFactory->create();
        $addressModel->updateData($address);
        $addressModel->setStoreId($this->storeManagerInterface->getStore()->getId());

        $errors = $addressModel->validate();
        if ($errors !== true) {
            $inputException = new InputException();
            foreach ($errors as $error) {
                $inputException->addError($error);
            }
            throw $inputException;
        }
        $addressModel->save();
        $address->setId($addressModel->getId());

        $this->addressRegistry->push($addressModel);
        return $addressModel->getDataModel();
    }

    /**
     * Update an address - core address creation requires a customer
     *
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return \Magento\Customer\Model\Address $addressModel
     * 
     */
    public function updateShippingAddress(\Magento\Customer\Api\Data\AddressInterface $address)
    {
        /** @var \Magento\Customer\Model\Address $addressModel */
        $addressModel = $this->addressFactory->create();
        $addressModel->updateData($address);
        $addressModel->setStoreId($this->storeManagerInterface->getStore()->getId());

        $errors = $addressModel->validate();
        if ($errors !== true) {
            $inputException = new InputException();
            foreach ($errors as $error) {
                $inputException->addError($error);
            }
            throw $inputException;
        }
        $addressModel->save();

        $this->addressRegistry->push($addressModel);
        return $addressModel->getDataModel();
    }

    /**
     * Checks if a billing address is available in the DB
     *
     * @param int $billingAddressId
     * @return boolean
     * 
     */
    public function isCustomerBillingAddressValid($billingAddressId)
    {
        try {
            if(!$billingAddressId){
                throw new \Exception("Null billing address id");
            }

            $address = $this->addressRepository->getById($billingAddressId);
            return true;
        } catch(\Exception $e){
            return false;
        }
    }

    /**
     * Checks if a shipping address is available in the given db and associated with a customer id
     *
     * @param int $shippingAddressId
     * @param int $customerId
     * @return boolean
     * 
     */
    public function isCustomerShippingAddressValid($shippingAddressId, $customerId)
    {
        try {
            if(!$shippingAddressId){
                throw new \Exception("Null shipping address id");
            }

            if(!$customerId){
                throw new \Exception("Null customer id");
            }

            $address = $this->addressRepository->getById($shippingAddressId);
            if($address->getCustomerId() != $customerId){
                throw new \Exception("Customer not address owner");
            }
            return true;
        } catch(\Exception $e){
            return false;
        }
    }

}