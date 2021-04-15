<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Block\Customer;

use Vonnda\Subscription\Model\SubscriptionCustomer;
use Vonnda\Subscription\Model\SubscriptionCustomerRepository;
use Vonnda\Subscription\Model\SubscriptionOrderRepository;
use Vonnda\Subscription\Model\SubscriptionPlanRepository;
use Vonnda\Subscription\Model\SubscriptionPaymentRepository;
use Vonnda\Subscription\Model\SubscriptionPaymentFactory;
use Vonnda\Subscription\Helper\AddressHelper;
use Vonnda\Subscription\Helper\StripeHelper as VonndaStripeHelper;
use Vonnda\Subscription\Helper\Logger;
use Vonnda\Subscription\Helper\Data as Helper;

use Carbon\Carbon;
use StripeIntegration\Payments\Model\StripeCustomerFactory;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\SessionFactory as CustomerSessionFactory;
use Magento\Sales\Model\Order\Payment\Repository as OrderPaymentRepository;
use Magento\Sales\Model\OrderRepository;

//TODO - this is out of sync with the methods contained in subscription management
class SubscriptionAddressManagement extends Template
{
    /**
     * Subscription Customer Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionCustomerRepository $subscriptionCustomerRepository
     */
    protected $subscriptionCustomerRepository;

    /**
     * Subscription Order Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionOrderRepository $subscriptionOrderRepository
     */
    protected $subscriptionOrderRepository;

    /**
     * Subscription Plan Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionPlanRepository $subscriptionPlanRepository
     */
    protected $subscriptionPlanRepository;

    /**
     * Subscription Payment Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionPaymentRepository $subscriptionPaymentRepository
     */
    protected $subscriptionPaymentRepository;

    /**
     * Subscription Payment Factory
     *
     * @var \Vonnda\Subscription\Model\SubscriptionPaymentFactory $subscriptionPaymentFactory
     */
    protected $subscriptionPaymentFactory;
    
    /**
     * Address Helper
     *
     * @var \Vonnda\Subscription\Helper\AddressHelper $addressHelper
     */
    protected $addressHelper;
    
    /**
     * Request Object
     *
     * @var \Magento\Framework\App\RequestInterface $request
     */
    protected $request;

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
     * Customer Session
     *
     * @var \Magento\Customer\Model\Session $customerSessionFactory
     */
    protected $customerSessionFactory;

    /**
     * Search Criteria Builder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Vonnda Logger
     *
     * @var \Vonnda\Subscription\Helper\Logger $logger
     */
    protected $logger;

    /**
     * Vonnda Stripe Helper
     *
     * @var \Vonnda\Subscription\Helper\StripeHelper $vonndaStripeHelper
     */
    protected $vonndaStripeHelper;

    /**
     * Vonnda Helper
     *
     * @var \Vonnda\Subscription\Helper\Data $helper
     */
    protected $helper;

    /**
     * Stripe Customer
     *
     * @var \StripeIntegration\Payments\Model\StripeCustomerFactory $stripeCustomerFactory
     */
    protected $stripeCustomerFactory;

    /**
     * Order Payment Repository
     *
     * @var \Magento\Sales\Model\Order\Payment\Repository $orderPaymentRepository
     */
    protected $orderPaymentRepository;

    /**
     * Order Repository
     *
     * @var \Magento\Sales\Model\OrderRepository $orderRepository
     */
    protected $orderRepository;

    /**
     * 
     * Subscription Customer Info Block Constructor
     * 
     * @param Context $context
     * @param RequestInterface $request
     * @param AddressRepositoryInterface $addressRepository
     * @param SubscriptionCustomerRepository $subscriptionCustomerRepository
     * @param SubscriptionOrderRepository $subscriptionOrderRepository
     * @param SubscriptionPlanRepository $subscriptionPlanRepository
     * @param SubscriptionPaymentRepository $subscriptionPaymentRepository
     * @param SubscriptionPaymentFactory $subscriptionPaymentFactory
     * @param AddressHelper $addressHelper
     * @param VonndaStripeHelper $vonndaStripeHelper
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderPaymentRepository $orderPaymentRepository
     * @param OrderRepository $orderRepository
     * @param Logger $logger
     * @param StripeCustomerfactory $stripeCustomerFactory
     * 
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        AddressRepositoryInterface $addressRepository,
        SubscriptionCustomerRepository $subscriptionCustomerRepository,
        SubscriptionOrderRepository $subscriptionOrderRepository,
        SubscriptionPlanRepository $subscriptionPlanRepository,
        SubscriptionPaymentRepository $subscriptionPaymentRepository,
        SubscriptionPaymentFactory $subscriptionPaymentFactory,
        AddressHelper $addressHelper,
        VonndaStripeHelper $vonndaStripeHelper,
        CustomerRepositoryInterface $customerRepository,
        CustomerSessionFactory $customerSessionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Logger $logger,
        OrderPaymentRepository $orderPaymentRepository,
        OrderRepository $orderRepository,
        StripeCustomerFactory $stripeCustomerFactory,
        Helper $helper
    ){
        $this->request = $request;
        $this->addressRepository = $addressRepository;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
        $this->subscriptionPaymentFactory = $subscriptionPaymentFactory;
        $this->subscriptionOrderRepository = $subscriptionOrderRepository;
        $this->subscriptionPlanRepository = $subscriptionPlanRepository;
        $this->addressHelper = $addressHelper;
        $this->vonndaStripeHelper = $vonndaStripeHelper;
        $this->customerRepository = $customerRepository;
        $this->customerSessionFactory = $customerSessionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->stripeCustomerFactory = $stripeCustomerFactory;
        $this->orderRepository = $orderRepository;
        $this->helper = $helper;
        parent::__construct($context);
	}


    /**
     * Get current customerId from session
     *
     * @param void
     * @return int $customerId
     *
     */
    public function getCurrentCustomerId()
    {
        try {
            $customerSession = $this->customerSessionFactory->create();
            $customerId = $customerSession->getCustomer()->getId();
            return $customerId;
        } catch(\Exception $e){
            return false;
        }
    }

    /**
     * Get customer subscriptions for customer
     *
     * @param void
     * @return \Vonnda\Subscription\Model\ResourceModel\SubscriptionCustomer\Collection
     *
     */
    public function getCustomerSubscriptions()
    {
        $store = $this->_storeManager->getStore();
        try {
            $customerId = $this->getCurrentCustomerId();
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('customer_id',$customerId,'eq')
                ->addFilter('status', SubscriptionCustomer::RETURNED_STATUS, 'neq')
                ->create();
            $subscriptionsFilteredForStore = [];
            $subscriptionCustomerList = $this->subscriptionCustomerRepository->getList($searchCriteria);
            foreach($subscriptionCustomerList->getItems() as $subscription){
                $subscriptionPlan = $subscription->getSubscriptionPlan();
                if($subscriptionPlan && ($subscriptionPlan->getStoreId() === $store->getId())){
                    $subscriptionsFilteredForStore[] = $subscription;
                }
            }
            return  $subscriptionsFilteredForStore;
        } catch(\Exception $e){
            return false;
        }
    }

    /**
     * Get subscription plan by id
     *
     * @param int $subscriptionPlanId
     * @return \Vonnda\Subscription\Model\SubscriptionPlan || false
     *
     */
    public function getSubscriptionPlanById(int $subscriptionPlanId)
    {
        try {
            $subscriptionPlan = $this->subscriptionPlanRepository->getById($subscriptionPlanId);
            return $subscriptionPlan;
        } catch(\Exception $e){
            return false;
        }
    }

    /**
     * Get shipping address by Id
     *
     * @param int $shippingAddressId
     * @return \Magento\Customer\Model\Address
     *
     */
    public function getAssociatedAddress($shippingAddressId)
    {
        try {
            if(!$shippingAddressId){
                return false;
            }
            return $this->addressRepository->getById($shippingAddressId);
        } catch(\Exception $e){
            return false;
        }
    }

    /**
     * Get card info string
     *
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @return string | boolean
     *
     */
    public function getCardInfoString(
        $subscriptionCustomer
    )
    {
        $subscriptionPayment = $subscriptionCustomer->getPayment();
        $card = null;
        if($subscriptionPayment){
            $card  = $this->vonndaStripeHelper->getCardFromCustomerIdAndPaymentCode(
                $subscriptionCustomer->getCustomerId(), $subscriptionPayment->getPaymentCode());
        } else {
            return "No card info available";
        }

        if($card){
            return $card->brand . " " . $card->last4 . " " . $card->exp_month . "/" . $card->exp_year;
        } else {
            return "No card info available";
        }
    }

    /**
     * Get billing address info that was used on that particular order
     *
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @return array
     *
     */
    public function getBillingAddress(
        $subscriptionCustomer
    ){
        try {
            $subscriptionPayment = $subscriptionCustomer->getPayment();
            return null;
            //return $subscriptionPayment->getBillingAddress();
        } catch (\Exception $e){
            $this->logger->info($e->getMessage());
            return false;
        }
    }


    /**
     * Output customer addresses in JSON
     *
     * @param int $customerId
     * @return array
     *
     */
    public function getCustomerAddressesJSON(int $customerId)
    {
        $customer = $this->customerRepository->getById($customerId);
        $addresses = $customer->getAddresses();
        $addressData = [];
        foreach($addresses as $address){
            $street = $address->getStreet();
            $streetOne = $street[0];
            $streetTwo = false;
            if(isset($street[1])){
                $streetTwo = $street[1];
            }
            $addressData[$address->getId()] = [
                "firstname" => $address->getFirstname(),
                "lastname" => $address->getLastname(),
                "streetOne" => $streetOne,
                "streetTwo" => $streetTwo ? $streetTwo : "",
                "country" =>$address->getCountryId(),
                "state" => $address->getRegion()->getRegionCode(),
                "postcode" => $address->getPostcode(),
                "telephone" => $address->getTelephone()];
        }

        return json_encode($addressData);
    }

    /**
     * Get customer address collection
     *
     * @param int $customerId
     * @return array
     *
     */
    public function getCustomerAddresses(int $customerId)
    {
        $customer = $this->customerRepository->getById($customerId);
        return $customer->getAddresses();
    }

    /**
     * Get customer payments
     *
     * @param int $customerId
     * @return array
     *
     */
    public function getCustomerPayments(int $customerId)
    {
        $subscriptionCustomers = $this->getCustomerSubscriptions($customerId);
        $data = [];
        if(count($subscriptionCustomers) === 0){
            return $data;
        }
        foreach($subscriptionCustomers as $subscriptionCustomer){
            try {
                $cardInfo = $this->getCardInfo($subscriptionCustomer);
                $billingAddress = $this->getBillingAddress($subscriptionCustomer);

                $subscriptionPayment = $subscriptionCustomer->getPayment();

                $cardInfo = $this->getCardInfoString($subscriptionCustomer);

                $data[] = [
                    "cardInfo" => $cardInfo,
                    "billingAddress" => $billingAddress,
                    "subscriptionPayment" => $subscriptionPayment,
                    "subscriptionCustomerId" => $subscriptionCustomer->getId()
                ];
            } catch(\Exception $e){

            }
        }
        return $data;
    }

    /**
     * Get customer cards for select field
     *
     * @param int $customerId
     * @return array
     * 
     */
    public function getAllCardsWithPaymentCode(int $customerId)
    {
        $stripeCustomer = $this->vonndaStripeHelper->getStripeCustomerFromCustomerId($customerId);

        $cardInfo = [];
        $cards = $this->vonndaStripeHelper->getAllCustomerCards($customerId);
        if($cards){
            foreach($cards as $card){
                
                $cardInfo[] = [
                    'paymentCode' => $card->id,
                    'cardInfoFull' => $card->brand . " " . $card->last4 . " " . $card->exp_month . "/" . $card->exp_year,
                    'cardBrandAndLast4' => $card->brand . " " . $card->last4,
                    'cardExpiration' =>  $card->exp_month . "/" . $card->exp_year,
                    'stripeCustomerId' => $stripeCustomer->getId()
                ];
            }
        }

        return $cardInfo;
    }

    public function getAutoCompleteApiKey()
    {
        return $this->helper->getConfigValue('aw_osc/general/google_places_api_key');
    }

    public function getWebsiteCode()
    {
        return $this->_storeManager->getStore()->getWebsite()->getCode();
    }
    
    public function getStoreCountryCode()
    {
        $storeCode = $this->_storeManager->getStore()->getCode();
        if($storeCode === Helper::STORE_CODE_US){
            return "us";
        } elseif($storeCode === Helper::STORE_CODE_CA){
            return "ca";
        }

        return "us";
    }

}