<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Helper;

use Vonnda\Subscription\Api\SubscriptionPromoRepositoryInterface;
use Vonnda\Subscription\Api\SubscriptionCustomerRepositoryInterface;
use Vonnda\Subscription\Api\SubscriptionPlanRepositoryInterface;
use Vonnda\Subscription\Helper\Logger;

use Carbon\Carbon;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\SalesRule\Model\CouponGenerator;
use Magento\SalesRule\Api\CouponRepositoryInterface;
use Magento\SalesRule\Model\ResourceModel\Coupon\UsageFactory;
use Magento\Framework\DataObjectFactory;

class PromoHelper extends AbstractHelper
{
    const DEFAULT_COUPON_CODE_LENGTH = 10;

    const DEFAULT_COUPON_CODE_PREFX = 'SUB-';

    /**
     * Vonnda Logger
     *
     * @var \Magento\Sales\Helper\Logger $logger
     */
    protected $logger;

    /**
     * Subscription Promo Repository
     *
     * @var \Vonnda\Subscription\Api\SubscriptionPromoRepositoryInterface $subscriptionPromoRepository
     */
    protected $subscriptionPromoRepository;

    /**
     * Subscription Customer Repository
     *
     * @var \Vonnda\Subscription\Api\SubscriptionCustomerRepositoryInterface $subscriptionCustomerRepository
     */
    protected $subscriptionCustomerRepository;

    /**
     * Subscription Plan Repository
     *
     * @var \Vonnda\Subscription\Api\SubscriptionPlanRepositoryInterface $subscriptionPlanRepository
     */
    protected $subscriptionPlanRepository;

    /**
     * Sales Rule Repository
     *
     * @var \Magento\SalesRule\Api\RuleRepositoryInterface $salesRuleRepository
     */
    protected $salesRuleRepository;

    /**
     * Product Repository
     *
     * @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     */
    protected $productRepository;

    /**
     * Search Criteria Builder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Cart Repository
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     */
    protected $cartRepository;

    /**
     * Cart Management
     *
     * @var \Magento\Quote\Api\CartManagementInterface $cartManagement
     */
    protected $cartManagement;

    /**
     * Customer Repository
     *
     * @var \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    protected $customerRepository;

    /**
     * Coupon Generator
     *
     * @var \Magento\SalesRule\Model\CouponGenerator $couponGenerator
     */
    protected $couponGenerator;

    /**
     * Coupon Repository
     *
     * @var \Magento\SalesRule\Api\CouponRepositoryInterface $couponRepository
     */
    protected $couponRepository;

    /**
     * Usage Factory
     *
     * @var \Magento\SalesRule\Model\ResourceModel\Coupon\UsageFactory $usageFactory
     */
    protected $usageFactory;

    /**
     * Data Object Factory
     *
     * @var \Magento\Framework\DataObjectFactory $dataObjectFactory
     */
    protected $dataObjectFactory;


    public function __construct(
        Logger $logger,
        SubscriptionPromoRepositoryInterface $subscriptionPromoRepository,
        SubscriptionCustomerRepositoryInterface $subscriptionCustomerRepository,
        SubscriptionPlanRepositoryInterface $subscriptionPlanRepository,
        RuleRepositoryInterface $salesRuleRepository,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CartRepositoryInterface $cartRepository,
        CartManagementInterface $cartManagement,
        CustomerRepositoryInterface $customerRepository,
        CouponGenerator $couponGenerator,
        CouponRepositoryInterface $couponRepository,
        UsageFactory $usageFactory,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->logger = $logger;
        $this->subscriptionPromoRepository = $subscriptionPromoRepository;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->subscriptionPlanRepository = $subscriptionPlanRepository;
        $this->salesRuleRepository = $salesRuleRepository;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->cartRepository = $cartRepository;
        $this->cartManagement = $cartManagement;
        $this->customerRepository = $customerRepository;
        $this->couponGenerator = $couponGenerator;
        $this->couponRepository = $couponRepository;
        $this->usageFactory = $usageFactory;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    //Simple validation - checks if rule is within time dates and is active
    public function ruleIdIsValid(int $ruleId)
    {
        $salesRule = $this->salesRuleRepository->getById($ruleId);

        $now = Carbon::now();
        $afterFromDate = false;
        $afterFromDateApplies = $salesRule->getFromDate();
        if ($afterFromDateApplies) {
            $fromDate = Carbon::parse($salesRule->getFromDate());
            $afterFromDate = $now >= $fromDate;
            if (!$afterFromDate) {
                throw new \Exception('Sales rule not valid yet');
            }
        }

        $beforeToDate = false;
        $beforeToDateApplies = $salesRule->getToDate();
        if ($beforeToDateApplies) {
            $toDate = Carbon::parse($salesRule->getToDate());
            $beforeToDate = $now < $toDate;
            if (!$beforeToDate) {
                throw new \Exception('Sales rule expired');
            }
        }

        if (!$salesRule->getIsActive()) {
            throw new \Exception('Sales rule not active');
        }

        return true;
    }

    //Checks is coupon code exists, is within usage limit, and not expired
    public function couponCodeIsValid(string $couponCode, int $customerId = null)
    {
        $searchCriteria = $this->searchCriteriaBuilder
                               ->addFilter('code', $couponCode,'eq')
                               ->create();
        $couponList = $this->couponRepository->getList($searchCriteria);
        $coupon = false;
        foreach($couponList->getItems() as $item){
            $coupon = $item;
            break;
        }

        if(!$coupon){
            throw new \Exception('Coupon not found for code ' . $couponCode);
        }
        
        $now = Carbon::now();
        if($coupon->getExpirationDate()){
            $isExpired = $now > Carbon::parse($coupon->getExpirationDate());
            if($isExpired){
                throw new \Exception('Coupon past expiration ' . $couponCode);
            }
        }

        if($coupon->getUsageLimit() && $coupon->getTimesUsed() >= $coupon->getUsageLimit()){
            throw new \Exception('Coupon usage limit exceeded');
        }

        if ($customerId && $coupon->getUsagePerCustomer()) {
            $couponUsage = $this->dataObjectFactory->create();
            $this->usageFactory->create()->loadByCustomerCoupon(
                $couponUsage,
                $customerId,
                $coupon->getId()
            );
            if ($couponUsage->getCouponId() &&
                $couponUsage->getTimesUsed() >= $coupon->getUsagePerCustomer()
            ) {
                throw new \Exception('Coupon usage limit exceeded for customer');
            }
        }

        //Check for the same single use coupon code already associated with a different subscription_promo
        $searchCriteria = $this->searchCriteriaBuilder
                               ->addFilter('coupon_code', $couponCode,'eq')
                               ->create();
        $subscriptionPromoList = $this->subscriptionPromoRepository->getList($searchCriteria);
        $couponCodeIsPending = false;
        foreach($subscriptionPromoList->getItems() as $item){
            $subscriptionPromo = $item;
            $couponCodeIsPending = $coupon->getUsageLimit() == 1 && 
                                   $coupon->getTimesUsed() < $coupon->getUsageLimit();

            if($couponCodeIsPending){
                throw new \Exception('Coupon pending use on an existing promo');
            }
        }

        return true;
    }

    public function generateSingleCouponCode(int $ruleId)
    {
        $params = [
            'length' => self::DEFAULT_COUPON_CODE_LENGTH,
            'prefix' => self::DEFAULT_COUPON_CODE_PREFX
        ];
        $couponCodeArr = $this->generateCouponCode(1, (int)$ruleId, $params);
        if (isset($couponCodeArr[0])) {
            return $couponCodeArr[0];
        } else {
            throw new \Exception('Error creating coupon code from promo');
        }
    }

    protected function generateCouponCode(
        int $qty,
        int $ruleId,
        array $params = []
    ) {
        if (!$qty || !$ruleId) {
            return;
        }

        $params['rule_id'] = $ruleId;
        $params['qty'] = $qty;

        return $this->couponGenerator->generateCodes($params);
    }
}
