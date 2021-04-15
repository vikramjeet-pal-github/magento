<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Block\Adminhtml\SubscriptionCustomer;

use Vonnda\Subscription\Model\SubscriptionCustomerRepository;
use Vonnda\Subscription\Model\SubscriptionPromoRepository;
use Vonnda\Subscription\Model\SubscriptionPaymentRepository;
use Vonnda\Subscription\Model\SubscriptionPlanRepository;
use Vonnda\DeviceManager\Api\DeviceManagerRepositoryInterface;
use Vonnda\Subscription\Helper\AddressHelper;
use Vonnda\Subscription\Helper\StripeHelper;
use Vonnda\Subscription\Helper\Logger;

use Carbon\Carbon;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Backend\Model\UrlInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;


class InfoBlock extends Template
{
    /**
     * Subscription Customer Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionCustomerRepository $subscriptionCustomerRepository
     */
    protected $subscriptionCustomerRepository;

    /**
     * Subscription Promo Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionPromoRepository $subscriptionPromoRepository
     */
    protected $subscriptionPromoRepository;

    /**
     * Subscription Payment Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionPaymentRepository $subscriptionPaymentRepository
     */
    protected $subscriptionPaymentRepository;

    /**
     * Subscription Plan Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionPlanRepository $subscriptionPlanRepository
     */
    protected $subscriptionPlanRepository;

    /**
     * Device Manager Repository
     *
     * @var \Vonnda\DeviceManager\Api\DeviceManagerRepositoryInterface $subscriptionDeviceRepository
     */
    protected $subscriptionDeviceRepository;
    
    /**
     * Address Helper
     *
     * @var \Vonnda\Subscription\Helper\AddressHelper $addressHelper
     */
    protected $addressHelper;

    /**
     * Stripe Helper
     *
     * @var \Vonnda\Subscription\Helper\StripeHelper $stripeHelper
     */
    protected $stripeHelper;
    
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
     * Backend Url
     *
     * @var \Magento\Backend\Model\UrlInterface $backendUrlInterface
     */
    protected $backendUrlInterface;

    /**
     * Customer Repository Interface
     *
     * @var \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    protected $customerRepository;

    /**
     * Sales Rule Repository
     *
     * @var \Magento\SalesRule\Api\RuleRepositoryInterface $salesRuleRepository
     */
    protected $salesRuleRepository;

    /**
     * Search Criteria Builder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    protected $logger;
    
    /**
     * 
     * Subscription Customer Info Block Constructor
     * 
     * @param Context $context
     * @param RequestInterface $request
     * @param AddressRepositoryInterface $addressRepository
     * @param SubscriptionCustomerRepository $subscriptionCustomerRepository
     * @param SubscriptionPromoRepository $subscriptionPromoRepository
     * @param SubscriptionPaymentRepository $subscriptionPaymentRepository
     * @param SubscriptionPlanRepository $subscriptionPlanRepository
     * @param DeviceManagerRepositoryInterface $deviceManagerRepository
     * @param AddressHelper $addressHelper
     * @param StripeHelper $stripeHelper
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param UrlInterface $backendUrlInterface
     * @param RuleRepositoryInterface $salesRuleRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * 
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        AddressRepositoryInterface $addressRepository,
        SubscriptionCustomerRepository $subscriptionCustomerRepository,
        SubscriptionPromoRepository $subscriptionPromoRepository,
        SubscriptionPaymentRepository $subscriptionPaymentRepository,
        SubscriptionPlanRepository $subscriptionPlanRepository,
        DeviceManagerRepositoryInterface $deviceManagerRepository,
        AddressHelper $addressHelper,
        StripeHelper $stripeHelper,
        CustomerRepositoryInterface $customerRepository,
        UrlInterface $backendUrlInterface,
        RuleRepositoryInterface $salesRuleRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Logger $logger
    ){
        $this->request = $request;
        $this->addressRepository = $addressRepository;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->subscriptionPromoRepository = $subscriptionPromoRepository;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
        $this->subscriptionPlanRepository = $subscriptionPlanRepository;
        $this->subscriptionDeviceRepository = $deviceManagerRepository;
        $this->addressHelper = $addressHelper;
        $this->stripeHelper = $stripeHelper;
        $this->customerRepository = $customerRepository;
        $this->backendUrlInterface = $backendUrlInterface;
        $this->salesRuleRepository = $salesRuleRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
        parent::__construct($context);
	}

    public function getSubscriptionCustomer()
    {
        $id = $this->request->getParam('id');
        if($id){
            try {
                return $this->subscriptionCustomerRepository->getById($id);
            } catch(\Exception $e){
                return false;
            }
        } else {
            return false;
        }
    }

    public function getCurrentDeviceInfo()
    {
        $subscriptionCustomer = $this->getSubscriptionCustomer();

        if($subscriptionCustomer && $subscriptionCustomer->getDeviceId()){
            try {
                $subscriptionDevice = $this->subscriptionDeviceRepository->getById($subscriptionCustomer->getDeviceId());
                $deviceInfo = [
                    'entity_id' => $subscriptionDevice->getEntityId(),
                    'serial_number' => $subscriptionDevice->getSerialNumber(),
                    'sku' => $subscriptionDevice->getSku()
                ];
                return json_encode($deviceInfo);
            } catch(\Exception $e){
                return null;
            }
        }
        return null;
    }

    public function getAvailablePromoCodes()
    {
        $searchCriteria = $this->searchCriteriaBuilder
                               ->addFilter('is_active', true,'eq')
                               ->addFilter('use_auto_generation', true,'eq')
                               ->create();
        $salesRuleList = $this->salesRuleRepository->getList($searchCriteria);
        $returnArr = [];
        foreach($salesRuleList->getItems() as $salesRule){
            $returnArr[] = [
                'name' => $salesRule->getName(),
                'id' => $salesRule->getRuleId()
            ];
        }

        return $returnArr;

    }

    public function getSubscriptionPromos()
    {
        $subscriptionCustomerId = $this->request->getParam('id');
        if($subscriptionCustomerId ){
            try {
                return $this->subscriptionPromoRepository
                            ->getListBySubscriptionCustomerId($subscriptionCustomerId);
            } catch(\Exception $e){
                return false;
            }
        } else {
            return false;
        }
    }

    public function getSubscriptionPromosJSON()
    {
        $subscriptionPromos = $this->getSubscriptionPromos();
        $returnData = [];
        if($subscriptionPromos){
            foreach($subscriptionPromos->getItems() as $promo){
                $returnData[] = [
                    'Id' => $promo->getId(),
                    'Coupon Code' => $promo->getCouponCode(),
                    'Used Status' => $promo->getUsedStatus() ? "Used" : "Active",
                    'Used At' => $promo->getUsedAt() ? 
                        $promo->getUsedAt() :
                        "N/A",
                    'Created At' => $promo->getCreatedAt()
                ];
            }
            return json_encode($returnData);
        }
        return false;
    }

    public function getAssociatedAddress($id)
    {
        try {
            return $this->addressRepository->getById($id);
        } catch(\Exception $e){
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
    public function getAddressFieldsByAddressId($addressId)
    {
        $address = $this->getAssociatedAddress($addressId);
        if($address){
            $addressData = [];
            $street = $address->getStreet();
            $streetOne = $street[0];
            $streetTwo = false;
            if(isset($street[1])){
                $streetTwo = $street[1];
            }
            $addressData = [
                "firstname" => $address->getFirstname(),
                "lastname" => $address->getLastname(),
                "streetOne" => $streetOne,
                "streetTwo" => $streetTwo ? $streetTwo : "",
                "city" => $address->getCity(),
                "country" =>$address->getCountryId(),
                "state" => $address->getRegion()->getRegionCode(),
                "postcode" => $address->getPostcode(),
                "telephone" => $address->getTelephone()];

            return $addressData;
        } else {
            return false;
        }
    }

    public function getCardInfoString($subscriptionCustomer)
    {
        $customerId = $subscriptionCustomer->getCustomerId();
        $subscriptionPayment  = $this->subscriptionPaymentRepository->getById($subscriptionCustomer->getSubscriptionPaymentId());
        if(!$subscriptionPayment){
            return "Subscription has no associated subscription payment";
        }

        if (!$subscriptionPayment->getPaymentCode()) {
            return "Payment has no associated card";
        }

        try {
            $card = $this->stripeHelper->getCardFromCustomerIdAndPaymentCode($customerId, $subscriptionPayment->getPaymentCode());
            return "Card: " . $card->brand . " " . $card->last4 . " " . $card->exp_month . "/" . $card->exp_year;
        } catch(\Exception $e){
            return "Couldn't get associated payment info<";
        }
    }

    public function getBillingAddressFields(int $subscriptionCustomerId)
    {
        try {
            $subscriptionPayment  = $this->subscriptionPaymentRepository->getSubscriptionPaymentBySubscriptionCustomerId($subscriptionCustomerId);
            if(!$subscriptionPayment) return false;
            $addressFields = $this->getAddressFieldsByAddressId($subscriptionPayment->getBillingAddressId());
            $addressFields['id'] = $subscriptionPayment->getBillingAddressId();
            return $addressFields;
        } catch(\Exception $e){
            return false;
        }
    }

    public function getCustomer($id)
    {
        try{
            return $this->customerRepository->getById($id);
        } catch(\Exception $e){
            return false;
        }
    }

    public function getSubscriptionPlanInfo($subscriptionCustomer)
    {
        try {
            $subscriptionPlan = $this->subscriptionPlanRepository->getById($subscriptionCustomer->getSubscriptionPlanId());
            return $subscriptionPlan->getTitle();
        } catch(\Exception $e){
            return "No tier found";
        }
    }

    public function getDevice($subscriptionCustomer)
    {
        try {
            $subscriptionDevice = $this->subscriptionDeviceRepository->getById($subscriptionCustomer->getDeviceId());
            return $subscriptionDevice;
        } catch(\Exception $e){
            return false;
        }
    }

    public function getCustomerEditUrl($customerId)
    {
        return $this->getUrl('customer/index/edit', ['id' => $customerId]);
    }

    public function getBackendAddPromoUrl()
    {
        return $this->backendUrlInterface->getUrl("vonnda_subscription/subscriptioncustomer/addpromo");
    }

    //TODO - move to new block
    public function getBackendGetCustomerInfoUrl()
    {
        return $this->backendUrlInterface->getUrl("vonnda_subscription/subscriptioncustomer/getcustomerinfo");
    }

    public function getBackendDeletePromoUrl()
    {
        return $this->backendUrlInterface->getUrl("vonnda_subscription/subscriptioncustomer/deletepromo");
    }

    public function getSubscriptionPayment()
    {
        try {
            $subscriptionCustomer = $this->getSubscriptionCustomer();
            if(!$subscriptionCustomer) return false;
            $subscriptionPayment  = $this->subscriptionPaymentRepository->getById($subscriptionCustomer->getSubscriptionPaymentId());
            if(!$subscriptionPayment) return false;
            return $subscriptionPayment;
        } catch(\Exception $e){
            return false;
        }

    }

}