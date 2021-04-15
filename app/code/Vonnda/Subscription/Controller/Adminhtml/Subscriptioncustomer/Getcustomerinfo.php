<?php 

namespace Vonnda\Subscription\Controller\Adminhtml\Subscriptioncustomer;  

use Vonnda\Subscription\Model\SubscriptionCustomerRepository;
use Vonnda\Subscription\Model\SubscriptionPaymentRepository;
use Vonnda\Subscription\Helper\StripeHelper;

use Carbon\Carbon;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;


class Getcustomerinfo extends Action 
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Vonnda_Subscription::manage';

    protected $resultJsonFactory;

    /**
     * Subscription Customer Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionCustomerRepository $subscriptionCustomerRepository
     */
    protected $subscriptionCustomerRepository;

    /**
     * Subscription Payment Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionPaymentRepository $subscriptionPaymentRepository
     */
    protected $subscriptionPaymentRepository;

    /**
     * Address Repository
     *
     * @var \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     */
    protected $addressRepository;

    /**
     * Customer Repository Interface
     *
     * @var \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    protected $customerRepository;

    /**
     * Stripe Helper
     *
     * @var \Vonnda\Subscription\Helper\StripeHelper $stripeHelper
     */
    protected $stripeHelper;

    /**
     * Search Criteria Builder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Auth Session
     *
     * @var \Magento\Backend\Model\Auth\Session $authSession
     */
    protected $authSession;
    
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        SubscriptionCustomerRepository $subscriptionCustomerRepository,
        SubscriptionPaymentRepository $subscriptionPaymentRepository,
        CustomerRepositoryInterface $customerRepository,
        AddressRepositoryInterface $addressRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StripeHelper $stripeHelper,
        Session $authSession
    ){
        $this->resultJsonFactory = $resultJsonFactory;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
        $this->customerRepository = $customerRepository;
        $this->addressRepository = $addressRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->stripeHelper = $stripeHelper;
        $this->authSession = $authSession;
        parent::__construct($context);
    }

    public function execute() { 
        $result = $this->resultJsonFactory->create();
        if ($this->getRequest()->isAjax()) 
        {
            $params = $this->getRequest()->getParams();
            $adminUserId = $this->authSession->getUser()->getId();
            $isValidRequest = isset($params['customerId']) && $params['customerId'];
            $customerId = $params['customerId'];
            
            if($isValidRequest){
                try {
                    $response = [
                        'Status'=>'success', 
                        'customerId' => $customerId,
                        'shippingAddresses' => $this->getCustomerAddresses($customerId),
                        'billingAddresses' => $this->getCustomerAddresses($customerId),
                        'paymentOptions' => $this->prepareCardFields($customerId)
                        ];
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

    public function getCustomerAddresses($customerId)
    {
        //TODO - separate into 2 functions, need to get addresses that were used for payments but without parentId
        try {
            $customer  = $this->customerRepository->getById($customerId);
            $optionArr = [['label' => "No address chosen", 'value' => ""]];
            $customerAddresses = $customer->getAddresses();
            foreach($customerAddresses as $address){
                $optionArr[] = [
                    "value" => $address->getId(),
                    "label" => $this->buildAddressString($address)
                ];
            }
            return $optionArr;
        } catch(\Exception $e){
            return false;
        }
    }

    protected function buildAddressString($address)
    {
        try {
            $shippingStreet = $address->getStreet();
            $shippingStreet = is_array($shippingStreet) ? implode("\n", $shippingStreet) : $shippingStreet;
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

    protected function prepareCardFields($customerId)
    {
        $cards = $this->stripeHelper->getAllCustomerCards($customerId);

        if (!$cards) {
            return [['label' => "No card chosen", 'value' => ""]];
        }

        $optionArr = [['label' => "No card chosen", 'value' => ""]];
        foreach($cards as $card){
            $optionArr[] = [
                'value' => $card->id,
                'label' => $card->brand . " " . $card->last4 . " " . $card->exp_month . "/" . $card->exp_year
            ];
        }

        return $optionArr;

    }

    protected function getUsedBillingAddressIds($customerId)
    {

    }

}