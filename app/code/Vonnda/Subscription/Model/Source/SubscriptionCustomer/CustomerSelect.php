<?php
//TODO - add a different form for new and edit - choose customer, dynamically adjust address
namespace Vonnda\Subscription\Model\Source\SubscriptionCustomer;

use Vonnda\Subscription\Model\SubscriptionCustomerRepository;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Customer\Api\AddressRepositoryInterface;

class CustomerSelect implements ArrayInterface
{
    protected $searchCriteriaBuilder;

    protected $addressRepositoryInterface;

    protected $customerRepositoryInterface;

    protected $subscriptionCustomerRepository;

    protected $requestInterface;

    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AddressRepositoryInterface $addressRepositoryInterface,
        CustomerRepositoryInterface $customerRepositoryInterface,
        SubscriptionCustomerRepository $subscriptionCustomerRepository,
        RequestInterface $requestInterface
    ){
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->addressRepositoryInterface = $addressRepositoryInterface;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->requestInterface = $requestInterface;
    }

    public function toOptionArray()
    {
        $dataArray = [];
        $addresses = [];

        $subscriptionCustomerId = $this->requestInterface->getParam("id");
        if($subscriptionCustomerId){
            try {
                $subscriptionCustomer = $this->subscriptionCustomerRepository->getById($subscriptionCustomerId);
                $customer = $this->customerRepositoryInterface->getById($subscriptionCustomer->getCustomerId());
                $addresses = $customer->getAddresses();
                foreach($addresses as $address){
                    $dataArray[] = ['value' => $address->getId(), 'label' => __($this->buildAddressString($address))];
                }
            } catch(\Exception $e){
                //TODO - how to handle this?
            }
        } else {
            $dataArray[] = ['value' => '', 'label' => __("Choose customer first")];
        }

        return $dataArray;
    }

    protected function buildAddressString($address)
    {
        $shippingStreet = $address->getStreet();
        $shippingStreet = is_array($shippingStreet) ? implode("\n", $shippingStreet) : $shippingStreet;

        try {
            $addressString = '';
            $addressString .= $address->getFirstname() . ' ' . $address->getLastname() . ', ';
            $addressString .= $shippingStreet . ', ';
            $addressString .= $address->getCity() . ', ';
            $addressString .= $address->getRegion()->getRegionCode() . ', ';
            $addressString .= $address->getPostcode() . ', ';
            $addressString .= $address->getCountryId();

            return $addressString;
        } catch(\Exception $e){
            return "Address not found";
        }
        
    }
}