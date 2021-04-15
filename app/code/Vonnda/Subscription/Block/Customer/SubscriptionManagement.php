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
use Vonnda\Subscription\Model\SubscriptionPayment;
use Vonnda\Subscription\Model\SubscriptionPaymentRepository;
use Vonnda\Subscription\Model\SubscriptionPaymentFactory;
use Vonnda\DeviceManager\Api\DeviceManagerRepositoryInterface;
use Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface;
use Vonnda\Subscription\Helper\AddressHelper;
use Vonnda\Subscription\Helper\StripeHelper as VonndaStripeHelper;
use Vonnda\Subscription\Helper\Logger;
use Vonnda\Subscription\Helper\TimeDateHelper;
use Vonnda\Subscription\Helper\Data as Helper;
use Vonnda\TealiumTags\Helper\Data as TealiumHelper;

use Carbon\Carbon;
use StripeIntegration\Payments\Model\StripeCustomerFactory;
use Aheadworks\OneStepCheckout\Model\Config as AWConfig;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\SessionFactory as CustomerSessionFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Sales\Model\Order\Payment\Repository as OrderPaymentRepository;
use Magento\Sales\Model\OrderRepository;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Config\Model\ResourceModel\Config\Data;
use Magento\Framework\Data\Form\Element\Time;
use Magento\CheckoutAgreements\Api\CheckoutAgreementsListInterface;


class SubscriptionManagement extends Template
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
     * Device Manager Repository
     *
     * @var \Vonnda\DeviceManager\Api\DeviceManagerRepositoryInterface $subscriptionDeviceRepository
     */
    protected $subscriptionDeviceRepository;

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
     * Helper
     *
     * @var \Vonnda\Subscription\Helper\Helper $helper
     */
    protected $helper;

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
     * Time Date Helper
     *
     * @var \Vonnda\Subscription\Helper\TimeDateHelper $timeDateHelper
     */
    protected $timeDateHelper;

    /**
     * Vonnda Stripe Helper
     *
     * @var \Vonnda\Subscription\Helper\StripeHelper $vonndaStripeHelper
     */
    protected $vonndaStripeHelper;

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
     * Order Repository
     *
     * @var \Magento\Sales\Model\OrderRepository $orderRepository
     */
    protected $dataObjectProcessor;

    /**
     * Order Repository
     *
     * @var \Magento\CheckoutAgreements\Api\CheckoutAgreementsListInterface $checkoutAgreementsList
     */
    protected $checkoutAgreementsList;

    protected $customerSession;

    protected $tealiumHelper;

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
     * @param CheckoutAgreementsListInterface $checkoutAgreementsList
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
        DeviceManagerRepositoryInterface $subscriptionDeviceRepository,
        SubscriptionPaymentFactory $subscriptionPaymentFactory,
        AddressHelper $addressHelper,
        VonndaStripeHelper $vonndaStripeHelper,
        CustomerRepositoryInterface $customerRepository,
        CustomerSessionFactory $customerSessionFactory,
        CustomerSession $customerSession,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Logger $logger,
        TimeDateHelper $timeDateHelper,
        OrderPaymentRepository $orderPaymentRepository,
        OrderRepository $orderRepository,
        StripeCustomerFactory $stripeCustomerFactory,
        DataObjectProcessor $dataObjectProcessor,
        CheckoutAgreementsListInterface $checkoutAgreementsList,
        Helper $helper,
        TealiumHelper $tealiumHelper
    ){
        $this->request = $request;
        $this->addressRepository = $addressRepository;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
        $this->subscriptionPaymentFactory = $subscriptionPaymentFactory;
        $this->subscriptionOrderRepository = $subscriptionOrderRepository;
        $this->subscriptionPlanRepository = $subscriptionPlanRepository;
        $this->subscriptionDeviceRepository = $subscriptionDeviceRepository;
        $this->addressHelper = $addressHelper;
        $this->vonndaStripeHelper = $vonndaStripeHelper;
        $this->customerRepository = $customerRepository;
        $this->customerSessionFactory = $customerSessionFactory;
        $this->customerSession = $customerSession;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
        $this->timeDateHelper = $timeDateHelper;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->stripeCustomerFactory = $stripeCustomerFactory;
        $this->orderRepository = $orderRepository;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->checkoutAgreementsList = $checkoutAgreementsList;
        $this->helper = $helper;
        $this->tealiumHelper = $tealiumHelper;
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
     * Get current customer email from session
     *
     * @param void
     * @return string $customerEmail|null
     *
     */
    public function getCurrentCustomerEmail()
    {
        try {
            $customerSession = $this->customerSessionFactory->create();
            $customerEmail = $customerSession->getCustomer()->getEmail();
            return $customerEmail;
        } catch(\Exception $e){
            return null;
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
            return [];
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
     * Check for default message on subscription
     *
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @param \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface $subscriptionPlan
     * @return boolean
     *
     */
    public function defaultMessageIsEnabled(
        \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
    ){
        return $subscriptionCustomer->getState() === 'inactive';
    }

    /**
     * Check if subscription will expire within a certain time period
     * TODO - Hard coded for one month, probably want to set this somewhere
     *
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @param \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface $subscriptionPlan
     * @return boolean
     *
     */
    public function subscriptionWillExpireSoon(
        \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer,
        \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface $subscriptionPlan
    ){
        if($this->isSubscriptionExpired($subscriptionCustomer, $subscriptionPlan)){
            return false;
        }

        if($subscriptionCustomer->getRenewalDateObject()){
            $inOneMonth = Carbon::now()->addMonth();
            $renewalDate = $subscriptionCustomer->getRenewalDateObject();
            if($renewalDate->lessThan($inOneMonth)){
                return true;
            }
        }

        return false;
    }

    /**
     * Get associated device for subscription
     *
     * @param void
     * @return void
     *
     */
    public function getSubscriptionDevice($subscriptionCustomer)
    {
        try {
            $subscriptionDevice = $this->subscriptionDeviceRepository->getById($subscriptionCustomer->getDeviceId());
            return $subscriptionDevice;
        } catch(\Exception $e){
            return false;
        }
    }

    /**
     * Returns the next order date which should be the last if it ran out
     *
     * @return string
     *
     */
    public function getExpirationDateString(
        \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
    )
    {
        if($subscriptionCustomer->getNextOrder()){
            $nextOrderDate = Carbon::createFromTimeString($subscriptionCustomer->getNextOrder())->setTimezone('America/Los_Angeles');
            $dateString = $nextOrderDate->format("m/d/Y");
            return $dateString;
        }

        return "";
    }

    /**
     * Get renewal date string
     *
     * @return string
     *
     */
    public function getNextShipmentDateString(
        \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
    )
    {
        $isExpired = $this->isSubscriptionExpired($subscriptionCustomer, $subscriptionCustomer->getSubscriptionPlan());
        $isInactive = $subscriptionCustomer->getState() === "inactive";
        $isErrorState = $this->subscriptionHasError($subscriptionCustomer);

        if($isExpired || $isInactive || $isErrorState){
            return "None scheduled";
        }
        if($subscriptionCustomer->getNextOrder()){
            $nextOrderDate = Carbon::createFromTimeString($subscriptionCustomer->getNextOrder())->setTimezone('America/Los_Angeles');
            $dateString = $nextOrderDate->format("m/d/Y");
            return $dateString;
        }

        return "None scheduled";
    }

    /**
     * Sort according to expiration status
     *
     * @param array $subscriptionList
     * @return array
     *
     */
    public function sortSubscriptionCustomers(
        $subscriptionList
    ){
        $hasErrorArr = [];
        $cardIsExpiredArr = [];
        $cardWillExpireArr = [];
        $isActivateEligibleArr = [];
        $isExpiredArr = [];
        $willExpireArr = [];
        $isInactiveArr = [];
        $isActiveArr = [];
        $newDeviceArr = [];

        if(count($subscriptionList) === 0){
            return [];
        }
        foreach($subscriptionList as $subscriptionCustomer){
            $subscriptionPlan = $this->getSubscriptionPlanById($subscriptionCustomer->getSubscriptionPlanId());
            $hasError = $this->subscriptionHasProcessingError($subscriptionCustomer);
            $cardIsExpired = $subscriptionCustomer->getStatus() === SubscriptionCustomer::PAYMENT_EXPIRED_STATUS;
            $cardWillExpire = $this->cardWillExpireSoon($subscriptionCustomer);
            $isActivateEligible = $this->isActivateEligible($subscriptionCustomer);
            $isExpired = $this->isSubscriptionExpired($subscriptionCustomer, $subscriptionPlan) &&
                !$this->isAutoRenewOnOrFree($subscriptionCustomer);
            $willExpire = $this->subscriptionWillExpireSoon($subscriptionCustomer, $subscriptionPlan) &&
                !$this->isAutoRenewOnOrFree($subscriptionCustomer);
            $isInactive = $this->subscriptionIsInactive($subscriptionCustomer);

            $newDeviceRegistration = $this->customerSession->getNewDeviceRegistrationId();
            $isNewDeviceSubscription = $newDeviceRegistration
                && ($newDeviceRegistration === $subscriptionCustomer->getId());

            if($isNewDeviceSubscription){
                $newDeviceArr[] = $subscriptionCustomer;
            } elseif($hasError){
                $hasErrorArr[] = $subscriptionCustomer;
            } elseif($cardIsExpired){
                $cardIsExpiredArr[] = $subscriptionCustomer;
            } elseif($cardWillExpire){
                $cardWillExpireArr[] = $subscriptionCustomer;
            } elseif($isActivateEligible){
                $isActivateEligibleArr[] = $subscriptionCustomer;
            } elseif($isExpired){
                $isExpiredArr[] = $subscriptionCustomer;
            } elseif($willExpire){
                $willExpireArr[] = $subscriptionCustomer;
            } elseif($isInactive){
                $isInactiveArr[] = $subscriptionCustomer;
            } else {
                $isActiveArr[] = $subscriptionCustomer;
            }
        }

        return array_merge($newDeviceArr,
                           $hasErrorArr,
                           $cardIsExpiredArr,
                           $cardWillExpireArr,
                           $isActivateEligibleArr,
                           $isExpiredArr,
                           $willExpireArr,
                           $isInactiveArr,
                           $isActiveArr);
    }

    /**
     * Sort according to criteria and return only top three
     *
     * @param array $subscriptionList
     * @return array
     *
     */
    public function getSubscriptionCustomersForDashboard(
        $subscriptionList,
        int $limit
    ){
        $sortedArr = $this->sortSubscriptionCustomers($subscriptionList);

        return array_slice($sortedArr, 0, $limit);
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
        try {
            $subscriptionPayment = $subscriptionCustomer->getPayment();
            $card = $this->vonndaStripeHelper->getCardFromCustomerIdAndPaymentCode(
                $subscriptionCustomer->getCustomerId(), $subscriptionPayment->getPaymentCode());
            if($card){
                return $card->brand . " " . $card->last4 . " <span class='cc-exp'>" . $card->exp_month . "/" . $card->exp_year . "</span>";
            } else {
                return "No card info available";
            }
        } catch(\Exception $e){
            return "No card info available";
        }
    }

    /**
     * Get card info string
     *
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @return string | boolean
     *
     */
    public function getCardInfo(
        $subscriptionCustomer
    )
    {
        $subscriptionPayment = $subscriptionCustomer->getPayment();
        $card = $this->vonndaStripeHelper->getCardFromCustomerIdAndPaymentCode(
            $subscriptionCustomer->getCustomerId(), $subscriptionPayment->getPaymentCode());
        if($card){
            return [
                'paymentCode' => $card->id,
                'cardInfoFull' => $card->brand . " " . $card->last4 . " " . $card->exp_month . "/" . $card->exp_year,
                'cardBrandAndLast4' => $card->brand . " " . $card->last4,
                'cardExpiration' =>  $card->exp_month . "/" . $card->exp_year
            ];
        } else {
            return null;
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
            $subscriptionPayment = $this->subscriptionPaymentRepository->getById($subscriptionCustomer->getSubscriptionPaymentId());
            return $subscriptionPayment->getBillingAddress();
        } catch (\Exception $e){
            return null;
        }
    }

    /**
     * Output customer addresses in JSON
     *
     * @param int $customerId
     * @return array
     *
     */
    public function getCustomerAddressesJSON($customerId)
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
                "city" => $address->getCity(),
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
    public function getCustomerAddresses($customerId)
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
        if($cards && is_array($cards)){
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

    /**
     * Count all successfull subscription orders for a subscription customer
     *
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @return int
     *
     */
    protected function countSuccessFullSubscriptionOrders(
        \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
    ){
        $searchCriteria = $this->searchCriteriaBuilder
                              ->addFilter('status', 'success', 'eq')
                              ->addFilter('subscription_customer_id', $subscriptionCustomer->getId(), 'eq')
                              ->create();
        $subscriptionOrderList = $this->subscriptionOrderRepository->getList($searchCriteria);
        return $subscriptionOrderList->getTotalCount();
    }

    /**
     * See if subscription has expired
     *
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @param \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface $subscriptionPlan
     * @return boolean
     *
     */
    //TODO - remove plan as parememter
    public function isSubscriptionExpired(
        \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer,
        \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface $subscriptionPlan
    ){
        if($subscriptionCustomer->getStatus() === SubscriptionCustomer::AUTORENEW_COMPLETE_STATUS){
            return true;
        }

        if(($subscriptionCustomer->getStatus() === SubscriptionCustomer::AUTORENEW_OFF_STATUS ||
            $subscriptionCustomer->getStatus() === SubscriptionCustomer::LEGACY_NO_PAYMENT_STATUS ||
            $subscriptionCustomer->getStatus() === SubscriptionCustomer::NEW_NO_PAYMENT_STATUS)
            && $this->renewalDateInPast($subscriptionCustomer)){
                return true;
            }

        if($subscriptionCustomer->getEndDate()){
            $now = Carbon::now();
            $endDate = Carbon::createFromTimeString($subscriptionCustomer->getEndDate())->setTimezone('America/Los_Angeles');
            if($endDate->lessThan($now)){
                return true;
            }
        }

        if(!$subscriptionPlan->getDuration()){
            return false;
        }

        //TODO - would the above ever not get set in the cron?
        $subscriptionCustomerWasRecharged = false;
        if(!$subscriptionCustomerWasRecharged){
            $numSubscriptionOrders = $this->countSuccessFullSubscriptionOrders($subscriptionCustomer);
            $subscriptionHasOrdersLeft = $numSubscriptionOrders < $subscriptionPlan->getDuration();
            if($subscriptionHasOrdersLeft){
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * Get number of orders left
     *
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @param \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface $subscriptionPlan
     * @return boolean
     *
     */
    protected function getNumOfSubscriptionOrdersLeft(
        \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer,
        \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface $subscriptionPlan
    ){
        $subscriptionCustomerWasRecharged = false;
        if(!$subscriptionCustomerWasRecharged){
            $numSubscriptionOrders = $this->countSuccessFullSubscriptionOrders($subscriptionCustomer);
            if($numSubscriptionOrders == 0){
                return $subscriptionPlan->getDuration();
            }
            if($numSubscriptionOrders >= $subscriptionPlan->getDuration()){
                return 0;
            }
            return $subscriptionPlan->getDuration() - $numSubscriptionOrders;
        } else {
            //TODO - right now return thing full amount on plan if recharged
            return $subscriptionPlan->getDuration();
        }
    }

    public function getAllSubscriptionsJSON(
        $customerId
    ){
        if($customerId){
            $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('customer_id', $customerId, 'eq')
            ->create();
            $subscriptionList = $this->subscriptionCustomerRepository->getList($searchCriteria);

            $subscriptions = [];
            foreach($subscriptionList->getItems() as $subscription){
                $subscriptions[$subscription->getId()] = $this->dataObjectProcessor->buildOutputDataArray($subscription, SubscriptionCustomerInterface::class);
            }

            return json_encode($subscriptions);
        }

        return null;
    }

    //Get devices eligible for auto renewal
    public function reformatDateString(
        $date
    ){
        try {
            $date = Carbon::createFromTimeString($date)->setTimezone('America/Los_Angeles');
            $dateString = $date->format("m/d/Y");
            return $dateString;
        } catch(\Exception $e){
            return "";
        }
    }

    public function customerHasExpiredSubscriptions(
        $customerId
    ){
        if($customerId){
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('customer_id', $customerId, 'eq')
                ->addFilter('status', SubscriptionCustomer::AUTORENEW_COMPLETE_STATUS, 'eq')
                ->create();
            $subscriptionList = $this->subscriptionCustomerRepository->getList($searchCriteria);

            return ($subscriptionList->getTotalCount() > 0);
        }

        return false;
    }

    //Or cards that will expire soon
    public function customerHasExpiredCards(
        $customerId
    ){
        if($customerId){
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('customer_id', $customerId, 'eq')
                ->create();
            $subscriptionList = $this->subscriptionCustomerRepository->getList($searchCriteria);

            foreach($subscriptionList->getItems() as $subscription){
                $subscriptionPayment = $subscription->getPayment();
                if($subscriptionPayment && $this->cardWillExpireSoon($subscription)){
                    return true;
                }
            }
        }

        return false;
    }

    //Will also return an expired card
    public function cardWillExpireSoon($subscriptionCustomer)
    {
        try {
            $subscriptionPayment = $subscriptionCustomer->getPayment();
            if (!$subscriptionPayment || !$subscriptionPayment->getPaymentCode()) {
                return false;
            }
            $card = $this->vonndaStripeHelper->getCardFromCustomerIdAndPaymentCode($subscriptionCustomer->getCustomerId(), $subscriptionPayment->getPaymentCode());
            $date = Carbon::createFromDate($card->exp_year, $card->exp_month, 28)->setTimezone('America/Los_Angeles');
            if($date < Carbon::now()->addMonth()){
                return true;
            }
            return false;
        } catch(\Exception $e){
            //Carbon can throw if expiration date is malformed
            return false;
        }
    }

    //TODO - refactor, make sure it is used correctly
    public function subscriptionIsInactive(
        $subscriptionCustomer
    ){
        return $subscriptionCustomer->getStatus() === SubscriptionCustomer::AUTORENEW_OFF_STATUS ||
               $subscriptionCustomer->getStatus() === SubscriptionCustomer::NEW_NO_PAYMENT_STATUS ||
               $subscriptionCustomer->getStatus() === SubscriptionCustomer::ACTIVATE_ELIGIBLE_STATUS;
    }

    public function getSerialOrDeviceName(
        $subscriptionCustomer
    ){
        $device = $subscriptionCustomer->getDevice();
        if(!$device){
            return "No device associated yet";
        }
        if($device->getSerialNumber()){
            return 'Molekule_'.substr($device->getSerialNumber(), -4, 4);
        }

        return "Molekule";
    }

    public function getAssociatedDevice(
        $subscriptionCustomer
    ){
        $device = $subscriptionCustomer->getDevice();
        if(!$device){
            return "No device associated yet";
        }
        if($device->getAssociatedProductName()){
            return $device->getAssociatedProductName();
        }

        return "N/A";
    }

    public function getAssociatedDeviceImage(
        $subscriptionCustomer
    ){
        $device = $subscriptionCustomer->getDevice();
        if(!$device){
            return "No device associated yet";
        }
        if($device->getAssociatedProductImage()){
            return '<img src="'.$device->getAssociatedProductImage().'" alt="'.$device->getAssociatedProductName().'" />';
        }

        return "N/A";
    }

    //This expects sorted subscription array
    public function getFirstSubToUpdate(
        $subscriptionArray
    ){
        foreach($subscriptionArray as $subscription){
            if($this->cardWillExpireSoon($subscription)){
                return $subscription->getId();
            }
        }

        return null;
    }

    public function isActivateEligible($subscription){
        return ($subscription->getStatus() === SubscriptionCustomer::ACTIVATE_ELIGIBLE_STATUS);
    }

    public function isAutoRenewOff($subscription){
        return ($subscription->getStatus() === SubscriptionCustomer::AUTORENEW_OFF_STATUS);
    }

    public function isAutoRenewComplete($subscription){
        return ($subscription->getStatus() === SubscriptionCustomer::AUTORENEW_COMPLETE_STATUS);
    }

    public function isLegacyNoPayment($subscription){
        return ($subscription->getStatus() === SubscriptionCustomer::LEGACY_NO_PAYMENT_STATUS);
    }

    public function isNewNoPayment($subscription){
        return ($subscription->getStatus() === SubscriptionCustomer::NEW_NO_PAYMENT_STATUS);
    }

    //For sort
    public function subscriptionHasProcessingError($subscription){
        return ($subscription->getStatus() === SubscriptionCustomer::PROCESSING_ERROR_STATUS||
                $subscription->getStatus() === SubscriptionCustomer::PAYMENT_INVALID_STATUS);
    }

    public function subscriptionHasError($subscription){
        return ($subscription->getStatus() === SubscriptionCustomer::PROCESSING_ERROR_STATUS||
                $this->subscriptionHasPaymentError($subscription));
    }

    public function subscriptionHasPaymentError($subscription){
        return ($subscription->getStatus() === SubscriptionCustomer::PAYMENT_EXPIRED_STATUS ||
                $subscription->getStatus() === SubscriptionCustomer::PAYMENT_INVALID_STATUS);
    }

    public function isAutoRenewOnOrFree($subscription){
        return ($subscription->getStatus() === SubscriptionCustomer::AUTORENEW_ON_STATUS ||
                $subscription->getStatus() === SubscriptionCustomer::AUTORENEW_FREE_STATUS);
    }

    public function isNoPayment($subscription){
        return ($subscription->getStatus() === SubscriptionCustomer::LEGACY_NO_PAYMENT_STATUS ||
                $subscription->getStatus() === SubscriptionCustomer::NEW_NO_PAYMENT_STATUS);
    }

    public function nextOrderDateInPast($subscription){
        try {
            $nextOrderDate = Carbon::createFromTimeString($subscription->getNextOrder())->setTimezone('America/Los_Angeles');
            $today = Carbon::today()->setTimezone('America/Los_Angeles');

            if($today > $nextOrderDate){
                return true;
            }

            return false;
        } catch(\Exception $e){
            $this->logger->info($e->getMessage());
        }
    }

    public function renewalDateInPast($subscription){
        try {
            $renewalDate = $subscription->getRenewalDateObject();
            $today = Carbon::today()->setTimezone('America/Los_Angeles');

            if($today > $renewalDate){
                return true;
            }

            return false;
        } catch(\Exception $e){
            $this->logger->info($e->getMessage());
        }
    }

    public function getTermsAndConditionsContent(){
        try {
            $name = 'Terms'; //Can be put into a config value
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('name', $name, 'eq')
                ->create();
            $checkoutAgreementList = $this->checkoutAgreementsList->getList($searchCriteria);
            foreach($checkoutAgreementList as $item){
                return $item->getContent();
            }

            return "";
        } catch(\Exception $e){
            $this->logger->info($e->getMessage());
            return "";
        }
    }

    public function firstNearExpiredSubscription($subscriptions){
        foreach($subscriptions as $subscription){
            $nearExpired = !$this->isSubscriptionExpired($subscription, $subscription->getSubscriptionPlan()) &&
                           $this->subscriptionWillExpireSoon($subscription, $subscription->getSubscriptionPlan()) &&
                           !$this->isAutoRenewOnOrFree($subscription);
            if($nearExpired){
                return $subscription;
            }
        }

        return false;
    }

    //TODO - would we want auto renew-complete to show?
    public function firstExpiredSubscription($subscriptions){
        foreach($subscriptions as $subscription){
            if($this->isSubscriptionExpired($subscription, $subscription->getSubscriptionPlan()) &&
                !$this->isAutoRenewComplete($subscription) &&
                !$this->isAutoRenewOnOrFree($subscription)){
                return $subscription;
            }
        }

        return false;
    }

    public function firstErrorSubscription($subscriptions){
        foreach($subscriptions as $subscription){
            if($this->subscriptionHasError($subscription)){
                return $subscription;
            }
        }

        return false;
    }

    public function firstActivateEligibleSubscription($subscriptions){
        foreach($subscriptions as $subscription){
            if($this->isActivateEligible($subscription)){
                return $subscription;
            }
        }

        return false;
    }

    public function getAutoCompleteApiKey()
    {
        return $this->helper->getConfigValue(AWConfig::XML_PATH_GOOGLE_PLACES_API_KEY);
    }

    public function shouldShowProcessingErrorMessage($subscription)
    {
        return $this->subscriptionHasProcessingError($subscription);
    }

    public function shouldShowActivateMessage($subscription)
    {
        return $this->isActivateEligible($subscription);
    }

    public function shouldShowExpiredMessage($subscription)
    {
        $isExpired = $this->isSubscriptionExpired($subscription, $subscription->getSubscriptionPlan());

        return $isExpired;
    }

    public function shouldShowAlmostExpiredMessage($subscription)
    {
        $isAutoRenewOff = $this->isAutoRenewOff($subscription);
        $isActivateEligible = $this->isActivateEligible($subscription);
        $isLegacyNoPayment = $this->isLegacyNoPayment($subscription);
        $isNewNoPayment = $this->isNewNoPayment($subscription);
        $subWillExpireSoon = $this->subscriptionWillExpireSoon($subscription, $subscription->getSubscriptionPlan());
        $isExpired = $this->isSubscriptionExpired($subscription, $subscription->getSubscriptionPlan());

        return (($isAutoRenewOff || $isActivateEligible || $isLegacyNoPayment || $isNewNoPayment) && $subWillExpireSoon && !$isExpired);
    }

    public function shouldShowTurnedOffMessage($subscription)
    {
        $isLegacyNoPayment = $this->isLegacyNoPayment($subscription);
        $isNewNoPayment = $this->isNewNoPayment($subscription);
        $isAutoRenewOff = $this->isAutoRenewOff($subscription);
        $isExpired = $this->isSubscriptionExpired($subscription, $subscription->getSubscriptionPlan());
        $subWillExpireSoon = $this->subscriptionWillExpireSoon($subscription, $subscription->getSubscriptionPlan());

        return ($isAutoRenewOff && !$isLegacyNoPayment && !$isNewNoPayment && !$isExpired &&!$subWillExpireSoon);
    }

    public function shouldShowExpirationDate($subscription)
    {
        $isExpired = $this->isSubscriptionExpired($subscription, $subscription->getSubscriptionPlan());
        $isAutoRenewOff = $this->isAutoRenewOff($subscription);
        $isActivateEligible = $this->isActivateEligible($subscription);
        $hasError = $this->subscriptionHasProcessingError($subscription);
        $isNoPayment = $this->isNoPayment($subscription);

        return $isExpired ||
            $isAutoRenewOff ||
            $isActivateEligible ||
            $hasError ||
            $isNoPayment ||
            !$subscription->getSubscriptionPaymentId();
    }

    public function getPaymentErrorMessage($subscription)
    {
        try {
            $message = "";
            $subscriptionPayment = $subscription->getPayment();
            $card = $this->vonndaStripeHelper->getCardFromCustomerIdAndPaymentCode(
                $subscription->getCustomerId(), $subscriptionPayment->getPaymentCode());
            if($card && $subscription->getStatus() === SubscriptionCustomer::PAYMENT_EXPIRED_STATUS){
                $message = "Card expired " . $card->exp_month . "/". $card->exp_year;
            }

            if($subscription->getStatus() === SubscriptionCustomer::PAYMENT_INVALID_STATUS){
                $message = "Payment declined";
            }

            if($card && $this->cardWillExpireSoon($subscription)){
                $message = "Card expires " . $card->exp_month . "/". $card->exp_year;
            }
        } catch (\Exception $e){

        }

        return $message;
    }

    public function shouldShowCardExpiredMessage($subscription)
    {
        return $subscription->getStatus() === SubscriptionCustomer::PAYMENT_EXPIRED_STATUS;
    }

    public function shouldShowActivationButton($subscription)
    {
        return $subscription->getState() === SubscriptionCustomer::INACTIVE_STATE &&
               !($subscription->getStatus() === SubscriptionCustomer::AUTORENEW_COMPLETE_STATUS);
    }

    public function shouldShowPaymentOnTile($subscription)
    {
        $hasValidPaymentFormOnSub = $subscription->getSubscriptionPaymentId() &&
            !($this->isNoPayment($subscription));

        if(!$subscription->getPayment() || !$subscription->getPayment()->getPaymentCode()){
            return false;
        }

        return $hasValidPaymentFormOnSub;
    }

    //Cancel Survey
    public function isAutoRefillCancelSurveyEnabled()
    {
        return $this->helper->isAutoRefillCancelEnabled();
    }

    public function getAutoRefillCancelQuestion()
    {
        return $this->helper->getAutoRefillCancelQuestion();
    }

    public function getCancelAutoRefillAnswers()
    {
        return $this->helper->getAutoRefillCancelAnswers();
    }

    public function getCustomerSessionId()
    {
        return $this->customerSession->getSessionId();
    }

    public function getTealiumCartInfoJSON()
    {
        return $this->tealiumHelper->getCartInfoJSON();
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

    public function getCMSBlockHtml($blockIdentifier, $templateVariables = [])
    {
        return $this->helper->getCMSBlockHtml($blockIdentifier, $templateVariables);
    }

    public function getCustomerUid()
    {
        $customer = $this->customerSession->getCustomer();
        $uuid = $customer->getCustomAttribute('cognito_uuid');
        $customerUid = ($uuid && $uuid->getValue()) ? $uuid->getValue() : "";
        return $customerUid;
    }

}
