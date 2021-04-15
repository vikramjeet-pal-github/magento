<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */
namespace Vonnda\Subscription\Controller\Adminhtml\Subscriptioncustomer;

use Magento\Backend\App\Action\Context;
use Vonnda\Subscription\Model\SubscriptionCustomerRepository;
use Vonnda\Subscription\Model\SubscriptionPaymentFactory;
use Vonnda\DeviceManager\Api\DeviceManagerRepositoryInterface;
use Vonnda\Subscription\Helper\StripeHelper;
use Vonnda\Subscription\Helper\DeviceHelper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Vonnda\Subscription\Helper\ValidationHelper;
use Vonnda\Subscription\Helper\EmailHelper;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Vonnda\TealiumTags\Helper\SubscriptionService as TealiumHelper;
use Vonnda\Subscription\Helper\Logger as SubscriptionLogger;
use Vonnda\Subscription\Model\SubscriptionCustomer;
use Vonnda\Subscription\Model\SubscriptionPayment;
use Carbon\Carbon;

class Edit extends \Magento\Backend\App\Action
{

    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Vonnda_Subscription::manage';
    
    /** @var SubscriptionCustomerRepository $subscriptionCustomerRepository */
    protected $subscriptionCustomerRepository;

    /** @var SubscriptionPaymentFactory $subscriptionPaymentFactory */
    protected $subscriptionPaymentFactory;

    /** @var DeviceManagerRepositoryInterface $subscriptionDeviceRepository */
    protected $subscriptionDeviceRepository;

    /** @var StripeHelper $stripeHelper */
    protected $stripeHelper;

    /** @var DeviceHelper $deviceHelper */
    protected $deviceHelper;

    /** @var TimezoneInterface $timezone */
    protected $timezone;

    /** @var ValidationHelper $validationHelper */
    protected $validationHelper;

    /** @var EmailHelper $emailHelper */
    protected $emailHelper;

    /** @var CustomerRepositoryInterface $customerRepository */
    protected $customerRepository;

    /** @var TealiumHelper $tealiumHelper */
    protected $tealiumHelper;

    /** @var SubscriptionLogger $subscriptionLogger */
    protected $subscriptionLogger;

    public function __construct(
        Context $context,
        SubscriptionCustomerRepository $subscriptionCustomerRepository,
        SubscriptionPaymentFactory $subscriptionPaymentFactory,
        StripeHelper $stripeHelper,
        DeviceHelper $deviceHelper,
        TimezoneInterface $timezone,
        ValidationHelper $validationHelper,
        EmailHelper $emailHelper,
        CustomerRepositoryInterface $customerRepository,
        TealiumHelper $tealiumHelper,
        SubscriptionLogger $subscriptionLogger
    ){
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->subscriptionPaymentFactory = $subscriptionPaymentFactory;
        $this->stripeHelper = $stripeHelper;
        $this->deviceHelper = $deviceHelper;
        $this->timezone = $timezone;
        $this->validationHelper = $validationHelper;
        $this->emailHelper = $emailHelper;
        $this->customerRepository = $customerRepository;
        $this->tealiumHelper = $tealiumHelper;
        $this->subscriptionLogger = $subscriptionLogger;
        parent::__construct($context);
    }

    /**
     * Edit SubscriptionCustomer Page
     * @return void|\Magento\Framework\Controller\Result\Redirect
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->renderLayout();
        $subscriptionCustomerData = $this->getRequest()->getParam('subscriptionCustomer');
        if (is_array($subscriptionCustomerData)) {
            $subscriptionCustomer = $this->subscriptionCustomerRepository->getById($subscriptionCustomerData['id']);
            $oldStatus = $subscriptionCustomer->getStatus();
            $resultRedirect = $this->resultRedirectFactory->create();
            $subscriptionCustomerData['updated_at'] = Carbon::now()->toDateTimeString();
            if ($subscriptionCustomerData['shipping_address_id'] == '') {
                $subscriptionCustomerData['shipping_address_id'] = null;
            }
            if (!$subscriptionCustomerData['device_id']) {
                $subscriptionCustomerData['device_id'] = null;
            }
            if (!$subscriptionCustomerData['subscription_payment_id']) {
                $subscriptionCustomerData['subscription_payment_id'] = null;
            }
            $deviceIdisValid = $subscriptionCustomerData['device_id']
                && $this->deviceHelper->subscriptionDeviceExist($subscriptionCustomerData['device_id']);
            if (!$deviceIdisValid && $subscriptionCustomerData['device_id']) {
                $subscriptionCustomerData['device_id'] = null;
                $this->messageManager->addError(__("Invalid device Id, device ID not set for subscription"));
            }
            $timeZone = $this->timezone->getConfigTimezone();
            //Must consider TZ in this case, otherwise it might show as the day earlier in the grid if set as midnight date
            if ($subscriptionCustomerData['next_order']) {
                $nextOrderDate = Carbon::createFromDate($subscriptionCustomerData['next_order']);
                $localDateTime = Carbon::now($timeZone);
                if ($nextOrderDate->isSameDay($localDateTime)) {
                    $nextOrder = Carbon::now()->toDateTimeString();
                } else {
                    $nextOrder = Carbon::createFromFormat("m/d/Y", $subscriptionCustomerData['next_order'], $timeZone)->toDateTimeString();
                }
                $subscriptionCustomerData['next_order'] = $nextOrder;
            }
            $subscriptionCustomerData = $this->handleShippingMethodOverwrite($subscriptionCustomerData, $subscriptionCustomer);
            try {
                $subscriptionCustomer->setData($subscriptionCustomerData);
                $subscriptionCustomer->setStatus($subscriptionCustomerData['status']);
            } catch (\Exception $e) {
                $this->messageManager->addError(__("There was an error editing this subscription"));
                return $resultRedirect->setPath('*/*/index');
            }
            $this->handleSubscriptionPayment($subscriptionCustomer, $subscriptionCustomerData);
            if ($subscriptionCustomer->getState() !== SubscriptionCustomer::ERROR_STATE) {
                $subscriptionCustomer->setErrorMessage(NULL);
            }   
            $this->subscriptionCustomerRepository->save($subscriptionCustomer);
            //this is may be used in the future to trigger an e-mail or something else on changing status to returned
            //$this->handleSubscriptionReturn($subscriptionCustomer, $initialSubscriptionStatus);
            $this->sendTealiumEvents($oldStatus, $subscriptionCustomer);
            $this->messageManager->addSuccess(__("Subscription edited"));
            return $resultRedirect->setPath('*/*/index');
        }
    }

    public function handleSubscriptionPayment($subscriptionCustomer, $subscriptionCustomerData)
    {
        $subscriptionPayment = $subscriptionCustomer->getPayment();
        if(!$subscriptionPayment){
            $this->handleNoSubscriptionPayment($subscriptionCustomer, $subscriptionCustomerData);
        } else {
            $this->handleExistingSubscriptionPayment($subscriptionCustomer, $subscriptionPayment);
        }
    }

    public function handleExistingSubscriptionPayment($subscriptionCustomer, $subscriptionPayment)
    {
        try {
            $stripeCustomer = $this->stripeHelper->getStripeCustomerFromCustomerId($subscriptionCustomer->getCustomerId());
            if ($subscriptionCustomer->getPaymentCode() != '' && $subscriptionCustomer->getPaymentCode() != null) {
                $card = $this->stripeHelper->getCardFromCustomerIdAndPaymentCode($subscriptionCustomer->getCustomerId(), $subscriptionCustomer->getPaymentCode());
                $subscriptionPayment->setStripeCustomerId($stripeCustomer->getId())
                    ->setStatus(SubscriptionPayment::VALID_STATUS)
                    ->setExpirationDate($card->exp_month . "/" . $card->exp_year)
                    ->setPaymentCode($subscriptionCustomer->getPaymentCode());
                $subscriptionCustomer->setPayment($subscriptionPayment);
            } elseif ($this->subscriptionCanBeEnabledWithoutPayment($subscriptionCustomer)) {
                return;
            } else {
                $subscriptionPayment->setStripeCustomerId($stripeCustomer->getId())
                    ->setStatus(SubscriptionPayment::INVALID_STATUS)
                    ->setExpirationDate(null)
                    ->setPaymentCode(null);
                $subscriptionCustomer->setStatus(SubscriptionCustomer::PAYMENT_INVALID_STATUS)
                    ->setPayment($subscriptionPayment);
            }
        } catch (\Exception $e) {
            $subscriptionCustomer->setStatus(SubscriptionCustomer::PAYMENT_INVALID_STATUS);
            $this->messageManager->addErrorMessage(__("Error creating payment"));
        }
    }

    public function handleNoSubscriptionPayment($subscriptionCustomer, $subscriptionCustomerData)
    {
        try {
            $subscriptionPaymentHasData = isset($subscriptionCustomerData['payment_code']) && $subscriptionCustomerData['payment_code'];
            if($this->subscriptionCanBeEnabledWithoutPayment($subscriptionCustomer) && !$subscriptionPaymentHasData){
                return;
            }
            $stripeCustomer = $this->stripeHelper->getStripeCustomerFromCustomerId($subscriptionCustomer->getCustomerId());
            $subscriptionPayment = $this->subscriptionPaymentFactory->create();
            $card = $this->stripeHelper->getCardFromCustomerIdAndPaymentCode($subscriptionCustomer->getCustomerId(), $subscriptionCustomer->getPaymentCode());
            $subscriptionPayment->setStripeCustomerId($stripeCustomer->getId())
                ->setStatus(SubscriptionPayment::VALID_STATUS)
                ->setExpirationDate($card->exp_month . "/" . $card->exp_year)
                ->setPaymentCode($subscriptionCustomer->getPaymentCode());
            $subscriptionCustomer->setPayment($subscriptionPayment);
        } catch(\Exception $e){
            $subscriptionCustomer->setStatus(SubscriptionCustomer::PAYMENT_INVALID_STATUS);
            $this->messageManager->addErrorMessage(__("Error creating payment"));
        }
    }

    public function subscriptionCanBeEnabledWithoutPayment($subscriptionCustomer)
    {
        $subscriptionPlan = $subscriptionCustomer->getSubscriptionPlan();
        if (!$subscriptionPlan->getPaymentRequiredForFree()) {
            return true;
        }
        return false;
    }

    public function handleShippingMethodOverwrite($subscriptionCustomerData, $subscriptionCustomer)
    {
        if ($subscriptionCustomerData['shipping_method_overwrite']) {
            $methodIsValid = $this->validationHelper
                ->shippingMethodIsValidForSubscription($subscriptionCustomer,$subscriptionCustomerData['shipping_method_overwrite']);
            if (!$methodIsValid) {
                //Leave it untouched
                unset($subscriptionCustomerData['shipping_method_overwrite']);
                unset($subscriptionCustomerData['shipping_cost_overwrite']);
            }
            $isOverwriteEqualZero = $this->isZeroNumber($subscriptionCustomerData['shipping_cost_overwrite']);
            if(!$subscriptionCustomerData['shipping_cost_overwrite'] && !($isOverwriteEqualZero)){
                $subscriptionCustomer->setShippingCostOverwriteToNull();
                unset($subscriptionCustomerData['shipping_cost_overwrite']);
            }
            return $subscriptionCustomerData;
        } else {
            $subscriptionCustomer->setShippingMethodOverwrite(null)
                ->setShippingCostOverwriteToNull();
            unset($subscriptionCustomerData['shipping_method_overwrite']);
            unset($subscriptionCustomerData['shipping_cost_overwrite']);
            return $subscriptionCustomerData;
        }
    }

    public function handleSubscriptionReturn($subscriptionCustomer, $initialSubscriptionStatus)
    {
        $subscriptionWasReturned = ($initialSubscriptionStatus !== SubscriptionCustomer::RETURNED_STATUS)
            && ($subscriptionCustomer->getStatus() === SubscriptionCustomer::RETURNED_STATUS);
        if($subscriptionWasReturned){
            try {
                $customer = $this->customerRepository->getById($subscriptionCustomer->getCustomerId());
                $this->emailHelper->sendSubscriptionReturnedEmail(
                    $customer, 
                    ['subscriptionCustomer' => $subscriptionCustomer]);
            } catch(\Exception $e){
                $this->messageManager->addError(__("There was an error sending the return e-mail"));
            }
        }
    }

    public function isZeroNumber($field)
    {
        if ($field === false || $field === "" || $field === null) {
            return false;
        }
        $num = floatval($field);
        if ($num == 0) {
            return true;
        }
        return false;
    }

    public function sendTealiumEvents($oldStatus, $subscriptionCustomer)
    {
        try {
            $customer = $this->customerRepository->getById($subscriptionCustomer->getCustomerId());
            $url = $this->_url->getUrl("vonnda_subscription/subscriptioncustomer/edit/id/" . $subscriptionCustomer->getId());
            $statusHasChanged = $subscriptionCustomer->getStatus() !== $oldStatus;
            $oldStatusWasError = $oldStatus === SubscriptionCustomer::PAYMENT_EXPIRED_STATUS
                || $oldStatus === SubscriptionCustomer::PAYMENT_INVALID_STATUS
                || $oldStatus === SubscriptionCustomer::PROCESSING_ERROR_STATUS;
            
            if (!$oldStatusWasError && $statusHasChanged && $subscriptionCustomer->getStatus() === SubscriptionCustomer::AUTORENEW_ON_STATUS) {
                $this->tealiumHelper->createActivateAutoRenewEvent($customer, $subscriptionCustomer, $url);
            }
            if ($statusHasChanged && $subscriptionCustomer->getStatus() === SubscriptionCustomer::AUTORENEW_OFF_STATUS) {
                $this->tealiumHelper->createTurnOffAutoRenewEvent($customer, $subscriptionCustomer, $url);
            }
        } catch (\Error $e) {
            $this->subscriptionLogger->critical($e->getMessage());
        } catch (\Exception $e) {
            $this->subscriptionLogger->info($e->getMessage());
        }
    }

}