<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Controller\Customer;

use Vonnda\Subscription\Model\SubscriptionCustomer;
use Vonnda\Subscription\Model\SubscriptionPayment;
use Vonnda\Subscription\Api\SubscriptionCustomerRepositoryInterface;
use Vonnda\Subscription\Api\SubscriptionServiceInterface;

use Carbon\Carbon;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;

class DeleteCard extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    protected $subscriptionCustomerRepository;

    protected $session;

    protected $searchCriteriaBuilder;

    protected $subscriptionServiceInterface;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \StripeIntegration\Payments\Model\Config $config,
        \StripeIntegration\Payments\Helper\Generic $helper,
        \StripeIntegration\Payments\Model\StripeCustomer $stripeCustomer,
        JsonFactory $resultJsonFactory,
        SubscriptionCustomerRepositoryInterface $subscriptionCustomerRepository,
        SubscriptionServiceInterface $subscriptionServiceInterface,
        Session $session,
        SearchCriteriaBuilder $searchCriteriaBuilder
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->subscriptionServiceInterface = $subscriptionServiceInterface;
        $this->config = $config;
        $this->helper = $helper;
        $this->stripeCustomer = $stripeCustomer;
        $this->session = $session;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        
        parent::__construct($context);
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        return $this->deleteCard($params);
    }

    public function deleteCard($params)
    {
        try {
            $result = $this->resultJsonFactory->create();

            if (!$this->session->isLoggedIn()){
                throw new \Exception('Unauthorized');
            }

            $requestIsValid = isset($params['token']) && $params['token'];
            if(!$requestIsValid){
                throw new \Exception('Invalid request');
            }

            $customerId = $this->session->getCustomer()->getId();
            $paymentCanBeDeleted = !$this->isCardUsedOnActiveSubscription($params['token'], $customerId);
            if(!$paymentCanBeDeleted){
                throw new \Exception('Existing active subscription');
            }

            $card = $this->stripeCustomer->deleteCard($params['token']);

            $this->handleSubscriptionPaymentsOnDelete($params['token'], $customerId);

            // In case we deleted a source
            if (isset($card->card)){
                $card = $card->card;
            }
            
            $response = [
                "status" => "success",
                "payment_code" => $params['token']//Source might be different
            ];

            $this->helper->addSuccess("Thanks for the update.");
            return $result->setData($response);
        } catch (\Stripe\Error\Card $e) {
            $result->setData(['status' => 'error','message' => $e->getMessage()]);
            return $result;
        } catch (\Stripe\Error $e) {
            $this->helper->logError($e->getMessage());
            $this->helper->logError($e->getTraceAsString());
            $result->setData(['status' => 'error','message' => $e->getMessage()]);
            return $result;
        } catch (\Exception $e) {
            $this->helper->logError($e->getMessage());
            $this->helper->logError($e->getTraceAsString());
            $result->setData(['status' => 'error','message' => $e->getMessage()]);
            return $result;
        }
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function isCardUsedOnActiveSubscription($paymentCode, $customerId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('customer_id',$customerId,'eq')
            ->addFilter('state', SubscriptionCustomer::ACTIVE_STATE, 'eq')
            ->create();
        $subcriptionList = $this->subscriptionCustomerRepository->getList($searchCriteria);
        foreach($subcriptionList->getItems() as $subcription){
            $payment = $subcription->getPayment();
            if($payment && $payment->getPaymentCode() === $paymentCode){
                return true;
            }
        }

        return false;
    }

    public function handleSubscriptionPaymentsOnDelete($paymentCode, $customerId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('customer_id',$customerId,'eq')
            ->addFilter('state', SubscriptionCustomer::ACTIVE_STATE, 'neq')
            ->create();
        $subcriptionList = $this->subscriptionCustomerRepository->getList($searchCriteria);
        foreach($subcriptionList->getItems() as $subcription){
            $payment = $subcription->getPayment();
            if($payment && $payment->getPaymentCode() === $paymentCode){
                $payment->setExpirationDate(null)
                        ->setStatus(SubscriptionPayment::INVALID_STATUS)
                        ->setPaymentCode(null);

                $subcription->setPayment($payment);
                
                //So we capture the changes
                $this->subscriptionServiceInterface->updateSubscriptionCustomer($subcription);
            }
        }
    }


}
