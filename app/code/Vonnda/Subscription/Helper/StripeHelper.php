<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Helper;

use Vonnda\Subscription\Helper\Logger as VonndaLogger;
use Vonnda\Subscription\Model\SubscriptionCustomerRepository;
use Vonnda\Subscription\Model\SubscriptionPaymentRepository;
use Vonnda\Subscription\Helper\TimeDateHelper;

use StripeIntegration\Payments\Model\StripeCustomer;
use StripeIntegration\Payments\Model\StripeCustomerFactory;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Sales\Model\Order\Payment\Repository as OrderPaymentRepository;

//An adapter to deal with Stripe
class StripeHelper extends AbstractHelper
{
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
     * Search Criteria Builder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Stripe Customer Factory
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
     * Vonnda Logger
     *
     * @var \Magento\Sales\Helper\Logger $logger
     */
    protected $logger;

    public function __construct(
        VonndaLogger $logger,
        SubscriptionCustomerRepository $subscriptionCustomerRepository,
        SubscriptionPaymentRepository $subscriptionPaymentRepository,
        StripeCustomerFactory $stripeCustomerFactory,
        OrderPaymentRepository $orderPaymentRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    )
    {
        $this->logger = $logger;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
        $this->stripeCustomerFactory = $stripeCustomerFactory;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    //This doesn't look at expiration date, only last 4
    public function getCardFromSubscriptionPayment(
        \Vonnda\Subscription\Api\Data\SubscriptionPaymentInterface $subscriptionPayment
    ){
        try {
            $stripeCustomerModel = $this->stripeCustomerFactory->create();
            $stripeCustomerModel->load($subscriptionPayment->getStripeCustomerId(), 'id');
            
            $cards= $stripeCustomerModel->getCustomerCards();
            if(!is_array($cards)){
                throw new \Exception("Stripe customer has no cards");
            }

            foreach ($cards as $card) {
                if ($subscriptionPayment->getPaymentCode() == $card->id) {
                    return $card;
                }
            }
            
            throw new \Exception("Stripe customer has no cards");

        } catch(\Exception $e){
            return false;
        }
    }

    public function getCardFromCustomerIdAndPaymentCode(
        $customerId,
        string $paymentCode
    ){
        if(!$customerId){
            return false;
        }
        try {
            $stripeCustomerModel = $this->stripeCustomerFactory->create();
            $stripeCustomerModel->load($customerId, 'customer_id');
            
            $cards = $stripeCustomerModel->getCustomerCards();
            if(!is_array($cards)){
                throw new \Exception("Stripe customer has no cards");
            }

            foreach ($cards as $card) {
                if ($paymentCode == $card->id) {
                    return $card;
                }
            }
            
            throw new \Exception("Card not found");

        } catch(\Exception $e){
            return false;
        }
    }

    public function getCardFromCustomerTokenAndCardFields($stripeCustomerModel, $last4, $expMonth, $expYear)
    {
        try {
            $cards= $stripeCustomerModel->getCustomerCards();
            if(!is_array($cards)){
                throw new \Exception("Stripe customer has no cards");
            }

            foreach ($cards as $card) {
                $cardIsSame = (int)$card->last4 == (int)$last4 &&
                              (int)$card->exp_month == (int)$expMonth &&
                              (int)$card->exp_year == (int)$expYear;
                
                if ($cardIsSame) {
                    return $card;
                }
            }

            return null;
        } catch(\Exception $e){
            return null;
        }
    }

    
    public function getAllCustomerCards(
        int $customerId
    ){
        try {
            $stripeCustomerModel = $this->stripeCustomerFactory->create();
            $stripeCustomerModel->load($customerId, 'customer_id');
            
            $cards= $stripeCustomerModel->getCustomerCards();
            if(!is_array($cards)){
                throw new \Exception("Stripe customer has no cards");
            }

            return $cards;
        } catch(\Exception $e){
            return false;
        }
    }

    public function getStripeCustomerFromCustomerId(
        int $customerId
    ){
        $stripeCustomerModel = $this->stripeCustomerFactory->create();
        $stripeCustomerModel->load($customerId, 'customer_id');

        return $stripeCustomerModel;
    }

    public function getStripeCustomerFromCustomerToken(
        $customerToken
    ){
        $stripeCustomerModel = $this->stripeCustomerFactory->create();
        $stripeCustomerModel->retrieveByStripeID($customerToken);

        return $stripeCustomerModel;
    }

    public function createStripeCustomerFromCustomerToken(
        $customerToken,
        $magentoCustomerId,
        $customerEmail
    ){
        $stripeCustomerModel = $this->stripeCustomerFactory->create();
        $stripeCustomerModel->retrieveByStripeID($customerToken);
        $stripeCustomerModel->setStripeID($customerToken);
        $stripeCustomerModel->setCustomerId($magentoCustomerId);
        $stripeCustomerModel->setCustomerEmail($customerEmail);
        $stripeCustomerModel->save();

        return $stripeCustomerModel;
    }

}