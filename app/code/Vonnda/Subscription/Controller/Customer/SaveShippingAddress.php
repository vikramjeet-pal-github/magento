<?php 

namespace Vonnda\Subscription\Controller\Customer;  

use Vonnda\Subscription\Model\SubscriptionCustomerRepository;
use Vonnda\Subscription\Helper\AddressHelper;

use Carbon\Carbon;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\Context;

//Address and Payment
class SaveShippingAddress extends Action {

    /**
     * Json Factory
     *
     * @var \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    protected $resultJsonFactory;

    /**
     * Subscription Customer Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionCustomerRepository $subscriptionCustomerRepository
     */
    protected $subscriptionCustomerRepository;

    /**
     * Address Helper
     *
     * @var \Vonnda\Subscription\Helper\AddressHelper $addressHelper
     */
    protected $addressHelper;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        SubscriptionCustomerRepository $subscriptionCustomerRepository,
        AddressHelper $addressHelper
    ){
        $this->resultJsonFactory = $resultJsonFactory;
        $this->addressHelper = $addressHelper;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        parent::__construct($context);
    }

    //TODO - this should be done with the API
    public function execute() { 
        $result = $this->resultJsonFactory->create();
        if ($this->getRequest()->isAjax()) 
        {
            $params = $this->getRequest()->getParams();
            $isValidRequest = true;
            $subscriptionCustomerId = intval($params['subscriptionCustomerId']);
            $customerId = intval($params['customerId']);
            $addressFields = $params['addressFields'];
            $addressFields['street'] = [$addressFields['streetOne'], $addressFields['streetTwo']];
            unset($addressFields['streetOne']);
            unset($addressFields['streetTwo']);

            if($isValidRequest){
                try {

                    $addressFields['customer_id'] = $customerId;
                    $addressInterface = $this->addressHelper->createAddressInterfaceFromData($addressFields);
                    $address = $this->addressHelper->createNewAddress($addressInterface);
                    
                    $subscriptionCustomer = $this->subscriptionCustomerRepository->getById($subscriptionCustomerId);
                    $subscriptionCustomer->setShippingAddressId($address->getId());
                    $this->subscriptionCustomerRepository->save($subscriptionCustomer);

                    //TODO - refactor
                    $addressArr = $addressFields;
                    $addressArr['streetOne'] = $address->getStreet()[0];
                    $addressArr['regionCode'] = $address->getRegion()->getRegionCode();

                    $response = [
                        'Status'=>'success', 
                        'subscriptionCustomerId' => $subscriptionCustomerId,
                        'customerId' =>  $customerId,
                        'street' =>  $subscriptionCustomer->getShippingAddress()->getStreet()[0],
                        'addressId' => $address->getId(),
                        'address' => $addressArr];
                } catch(\Exception $e){
                    $response = [
                        'Status'=>'error', 
                        'message' => $e->getMessage()];
                }
                return $result->setData($response);
            } else {
                $response = [
                    'Status'=>'error', 
                    'message' => 'Improper request'];
                return $result->setData($response);

            }
        }
    } 

}