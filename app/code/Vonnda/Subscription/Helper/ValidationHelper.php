<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Helper;

use Vonnda\Subscription\Helper\Logger;
use Vonnda\Subscription\Helper\StripeHelper;
use Vonnda\Subscription\Model\SubscriptionCustomerRepository;
use Vonnda\Subscription\Model\SubscriptionPaymentRepository;
use Vonnda\Subscription\Model\SubscriptionPlan;
use Vonnda\Subscription\Model\SubscriptionPlanRepository;
use Vonnda\Subscription\Model\SubscriptionOrderRepository;
use Vonnda\Subscription\Model\SubscriptionPromoRepository;
use Vonnda\Subscription\Model\SubscriptionHistoryRepository;
use Vonnda\DeviceManager\Api\DeviceManagerRepositoryInterface;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Shipping\Model\Config as ShippingConfig;
use Magento\Store\Model\StoreManagerInterface;

class ValidationHelper extends AbstractHelper
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
     * Subscription History Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionHistoryRepository $subscriptionHistoryRepository
     */
    protected $subscriptionHistoryRepository;

    /**
     * Subscription Device Repository
     *
     * @var \Vonnda\DeviceManager\Api\DeviceManagerRepositoryInterface $subscriptionDeviceRepository
     */
    protected $subscriptionDeviceRepository;

    /**
     * Vonnda Logger Helper
     *
     * @var \Vonnda\Subscription\Helper\Logger $logger
     */
    protected $logger;

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
     * Customer Repository Interface
     *
     * @var \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
     */
    protected $customerRepositoryInterface;

    /**
     * Address Repository
     *
     * @var \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     */
    protected $addressRepository;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Shipping\Model\Config
     */
    protected $shippingConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;


    public function __construct(
        Logger $logger,
        SubscriptionCustomerRepository $subscriptionCustomerRepository,
        SubscriptionPaymentRepository $subscriptionPaymentRepository,
        SubscriptionPlanRepository $subscriptionPlanRepository,
        SubscriptionOrderRepository $subscriptionOrderRepository,
        SubscriptionPromoRepository $subscriptionPromoRepository,
        SubscriptionHistoryRepository $subscriptionHistoryRepository,
        DeviceManagerRepositoryInterface $subscriptionDeviceRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CustomerRepositoryInterface $customerRepositoryInterface,
        StripeHelper $stripeHelper,
        AddressRepositoryInterface $addressRepository,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ShippingConfig $shippingConfig
    ) {
        $this->logger = $logger;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
        $this->subscriptionPlanRepository = $subscriptionPlanRepository;
        $this->subscriptionOrderRepository = $subscriptionOrderRepository;
        $this->subscriptionPromoRepository = $subscriptionPromoRepository;
        $this->subscriptionHistoryRepository = $subscriptionHistoryRepository;
        $this->subscriptionDeviceRepository = $subscriptionDeviceRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->stripeHelper = $stripeHelper;
        $this->addressRepository = $addressRepository;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->shippingConfig = $shippingConfig;
    }

    /**
     * Checks is subscription plan id is valid to set on subscription customer, i.e.
     * it exists, is visible, and active
     *
     * @param int $subscriptionPlanId
     * @return boolean
     * 
     */
    public function isSubscriptionPlanIdValid(int $subscriptionPlanId)
    {
        try {
            $subscriptionPlan = $this->subscriptionPlanRepository->getById($subscriptionPlanId);
            if($subscriptionPlan->getStatus() != SubscriptionPlan::ACTIVE_STATUS || !$subscriptionPlan->getVisible()){
                throw new \Exception("Forbidden tier");
            }
            return true;
        } catch(\Exception $e){
            return false;
        }
    }

    public function shippingMethodIsValidForSubscription($subscriptionCustomer, $method)
    {
        $customer = $this->customerRepositoryInterface->getById($subscriptionCustomer->getCustomerId());
        $customerStoreId = $customer->getStoreId();

        $stores = $this->storeManager->getStores();
        foreach($stores as $_store){
            $isStore = (int)$customerStoreId === (int)$_store->getId();
            if($isStore){
                $_carriers = $this->shippingConfig->getAllCarriers($_store->getId());
                foreach ($_carriers as $_carrierCode => $_carrierModel) {
                    if (!$_carrierModel->isActive()) {
                        continue;
                    }
                    $_carrierMethods = $_carrierModel->getAllowedMethods();
                    if (!$_carrierMethods) {
                        continue;
                    }

                    foreach ($_carrierMethods as $methodCode => $methodTitle) {
                        if($method === $_carrierCode . '_' . $methodCode){
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }
}
