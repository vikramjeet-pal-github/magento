<?php 

namespace Vonnda\Subscription\Controller\Customer;  

use Vonnda\Subscription\Model\SubscriptionCustomerRepository;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\Context;

//Address and Payment
//TODO - refactor FE to use API, remove this
class SwitchShippingAddress extends Action {

    protected $resultJsonFactory;

    /**
     * Subscription Customer Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionCustomerRepository $subscriptionCustomerRepository
     */
    protected $subscriptionCustomerRepository;
    
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        SubscriptionCustomerRepository $subscriptionCustomerRepository
    ){
        $this->resultJsonFactory = $resultJsonFactory;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        parent::__construct($context);
    }

    public function execute() { 
        $result = $this->resultJsonFactory->create();
        if ($this->getRequest()->isAjax()) 
        {
            $params = $this->getRequest()->getParams();
            $isValidRequest = isset($params['subscriptionCustomerId']) && isset($params['addressId']);
            $subscriptionCustomerId = intval($params['subscriptionCustomerId']);
            $addressId = intval($params['addressId']);

            if($isValidRequest){
                try {
                    $subscriptionCustomer = $this->subscriptionCustomerRepository->getById($subscriptionCustomerId);

                    $subscriptionCustomer->setShippingAddressId($addressId);
                    $this->subscriptionCustomerRepository->save($subscriptionCustomer);

                    $response = [
                        'Status'=>'success', 
                        'subscriptionCustomerId' => $subscriptionCustomerId,
                        'addressId' =>  $subscriptionCustomer->getShippingAddress()->getId(),
                        'street' =>  $subscriptionCustomer->getShippingAddress()->getStreet()[0]];
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
