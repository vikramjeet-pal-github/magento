<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Controller\Adminhtml\Subscriptioncustomer;

use Vonnda\Subscription\Model\SubscriptionCustomer;
use Vonnda\Subscription\Model\SubscriptionCustomerFactory;
use Vonnda\Subscription\Model\SubscriptionCustomerRepository;
use Vonnda\Subscription\Model\SubscriptionPlanRepository;
use Vonnda\Subscription\Model\SubscriptionPromoFactory;
use Vonnda\Subscription\Model\SubscriptionPromoRepository;
use Vonnda\Subscription\Model\SubscriptionPayment;
use Vonnda\Subscription\Model\SubscriptionPaymentFactory;
use Vonnda\Subscription\Helper\Logger;
use Vonnda\Subscription\Helper\PromoHelper;
use Vonnda\Subscription\Helper\DeviceHelper;
use Vonnda\Subscription\Helper\StripeHelper;
use Vonnda\DeviceManager\Model\DeviceManagerFactory;

use Carbon\Carbon;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;

class NewAction extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Vonnda_Subscription::manage';
    
    /**
     * Subscription Customer Factory
     *
     * @var \Vonnda\Subscription\Model\SubscriptionCustomerFactory $subscriptionCustomerFactory
     */
    protected $subscriptionCustomerFactory;
   
    /**
     * Subscription Customer Repository
     *
     * @var SubscriptionCustomerRepository $subscriptionCustomerRepository
     */
    protected $subscriptionCustomerRepository;

    /**
     * Subscription Device Factory
     *
     * @var \Vonnda\DeviceManager\Model\DeviceManagerFactory $subscriptionDeviceFactory
     */
    protected $subscriptionDeviceFactory;

    /**
     * Subscription Plan Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionPlanRepository $subscriptionPlanRepository
     */
    protected $subscriptionPlanRepository;

    /**
     * Subscription Promo Factory
     *
     * @var \Vonnda\Subscription\Model\SubscriptionPromoFactory $subscriptionPromoFactory
     */
    protected $subscriptionPromoFactory;

    /**
     * Subscription Promo Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionPromoRepository $subscriptionPromoRepository
     */
    protected $subscriptionPromoRepository;

    /**
     * Subscription Payment Factory
     *
     * @var \Vonnda\Subscription\Model\SubscriptionPaymentFactory $subscriptionPaymentFactory
     */
    protected $subscriptionPaymentFactory;

    /**
     * Subscription Promo Helper
     *
     * @var \Vonnda\Subscription\Helper\PromoHelper $promoHelper
     */
    protected $promoHelper;

    /**
     * Subscription Device Helper
     *
     * @var \Vonnda\Subscription\Helper\DeviceHelper $deviceHelper
     */
    protected $deviceHelper;

    /**
     * Stripe Helper
     *
     * @var \Vonnda\Subscription\Helper\StripeHelper $stripeHelper
     */
    protected $stripeHelper;

    /**
     * Subscription Logger
     *
     * @var \Vonnda\Subscription\Helper\Logger $logger
     */
    protected $logger;

    /**
     * Message Manager
     *
     * @var \Magento\Framework\Message\ManagerInterface $messageManager
     */
    protected $messageManager;

    /**
     * NewAction constructor.
     * @param Context $context
     * @param SubscriptionCustomerFactory $subscriptionCustomerFactory
     * @param SubscriptionCustomerRepository $subscriptionCustomerRepository
     * @param SubscriptionPlanRepository $subscriptionPlanRepository
     * @param SubscriptionPromoFactory $subscriptionPromoFactory
     * @param SubscriptionPromoRepository $subscriptionPromoRepository
     * @param SubscriptionPaymentFactory $subscriptionPaymentFactory
     * @param DeviceManagerFactory $subscriptionDeviceFactory
     * @param StripeHelper $stripeHelper
     * @param PromoHelper $promoHelper
     * @param DeviceHelper $deviceHelper
     * @param Logger $logger
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        Context $context,
        SubscriptionCustomerFactory $subscriptionCustomerFactory,
        SubscriptionCustomerRepository $subscriptionCustomerRepository,
        SubscriptionPlanRepository $subscriptionPlanRepository,
        SubscriptionPromoFactory $subscriptionPromoFactory,
        SubscriptionPromoRepository $subscriptionPromoRepository,
        SubscriptionPaymentFactory $subscriptionPaymentFactory,
        DeviceManagerFactory $subscriptionDeviceFactory,
        StripeHelper $stripeHelper,
        PromoHelper $promoHelper,
        DeviceHelper $deviceHelper,
        Logger $logger,
        ManagerInterface $messageManager
    ) {
        $this->subscriptionCustomerFactory = $subscriptionCustomerFactory;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->subscriptionPlanRepository = $subscriptionPlanRepository;
        $this->subscriptionPromoFactory = $subscriptionPromoFactory;
        $this->subscriptionPromoRepository = $subscriptionPromoRepository;
        $this->subscriptionPaymentFactory = $subscriptionPaymentFactory;
        $this->subscriptionDeviceFactory = $subscriptionDeviceFactory;
        $this->promoHelper = $promoHelper;
        $this->deviceHelper = $deviceHelper;
        $this->stripeHelper = $stripeHelper;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     * @throws \Exception
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->renderLayout();

        $subscriptionCustomerData = $this->getRequest()->getParam('subscriptionCustomer');
        if (is_array($subscriptionCustomerData)) {
            $subscriptionCustomer = $this->subscriptionCustomerFactory->create();
            $resultRedirect = $this->resultRedirectFactory->create();
            
            if (!$subscriptionCustomerData['shipping_address_id']) {
                $subscriptionCustomerData['shipping_address_id'] = null;
            }

            $subscriptionCustomerData['subscription_payment_id'] = null;

            try {
                $device = $this->createNewDevice($subscriptionCustomerData);
            } catch (\Exception $e) {
                $this->messageManager->addError(__("There was an error creating this subscription"));
                return $resultRedirect->setPath('*/*/index');
            }
            
            if ($subscriptionCustomerData['next_order']) {
                $nextOrder = Carbon::createFromDate($subscriptionCustomerData['next_order'])->setTime(12,0,0)->toDateTimeString();
                $subscriptionCustomerData['next_order'] = $nextOrder;
            }
            
            if (!$subscriptionCustomerData['customer_id']) {
                $this->messageManager->addError(__("You need to select a customer before saving the subscription"));
                return $resultRedirect->setPath('*/*/newAction');
            }

            $subscriptionCustomer->setData($subscriptionCustomerData);
            $subscriptionCustomer->setDeviceId($device->getId());

            try {
                $this->subscriptionCustomerRepository->save($subscriptionCustomer);
            } catch(\Exception $e){
                $this->messageManager->addError(__("There was an error creating this subscription"));
                return $resultRedirect->setPath('*/*/index');
            }

            $this->handleSubscriptionPayment($subscriptionCustomerData, $subscriptionCustomer);

            $this->setDefaultSubscriptionPromos($subscriptionCustomer);
            
            $this->messageManager->addSuccess(__("Subscription created"));
            return $resultRedirect->setPath('*/*/index');
        }
    }

    protected function setDefaultSubscriptionPromos(
        \Vonnda\Subscription\Model\SubscriptionCustomer $subscriptionCustomer
    ) {
        $subscriptionPlan = $this->subscriptionPlanRepository->getById($subscriptionCustomer->getSubscriptionPlanId());
        $promoIds = $subscriptionPlan->getDefaultPromoIds();
        $errorCreatingPromo = false;
        if ($promoIds) {
            $promoIdsArr = explode(',',$promoIds);
            $subscriptionCustomerId = $subscriptionCustomer->getId();
            foreach ($promoIdsArr as $ruleId) {
                try {
                    $couponCode = $this->promoHelper->generateSingleCouponCode($ruleId);
                    $subscriptionPromo = $this->subscriptionPromoFactory->create();
                    $subscriptionPromo->setSubscriptionCustomerId($subscriptionCustomerId)
                                      ->setCouponCode($couponCode);
                    $this->subscriptionPromoRepository->save($subscriptionPromo);
                } catch(\Exception $e) {
                    $errorCreatingPromo = true;
                    $this->logger->info("Error creating promo code");
                    $this->logger->info($e->getMessage());
                }
            }
        }

        if ($errorCreatingPromo) {
            $this->messageManager->addError(__("There was an error creating the default promos"));
        }
    }

    public function handleSubscriptionPayment($subscriptionCustomerData, $subscriptionCustomer)
    {
        $customerId = $subscriptionCustomer->getCustomerId();
        $subscriptionPaymentHasData = $subscriptionCustomerData['payment_code'] != '';
        try {
            if ($subscriptionPaymentHasData && $subscriptionCustomerData['subscription_payment_id']) {
                $stripeCustomer = $this->stripeHelper->getStripeCustomerFromCustomerId($customerId);
                $subscriptionPayment = $this->subscriptionPaymentFactory->create();
                $card = $this->stripeHelper->getCardFromCustomerIdAndPaymentCode($customerId, $subscriptionCustomerData['payment_code']);
                $subscriptionPayment->setStripeCustomerId($stripeCustomer->getId())
                                    ->setStatus(SubscriptionPayment::VALID_STATUS)
                                    ->setExpirationDate($card->exp_month . "/" . $card->exp_year)
                                    ->setPaymentCode($subscriptionCustomerData['payment_code']);
                $subscriptionCustomer->setStatus(SubscriptionCustomer::AUTORENEW_ON_STATUS)
                                     ->setPayment($subscriptionPayment);
            } elseif($this->subscriptionCanBeEnabledWithoutPayment($subscriptionCustomer)){
                $this->handleValidWithNoPayment($subscriptionCustomer);
            } else {
                $subscriptionCustomer->setStatus(SubscriptionCustomer::NEW_NO_PAYMENT_STATUS);
            }
            $this->subscriptionCustomerRepository->save($subscriptionCustomer);
        } catch (\Exception $e) {
            $subscriptionCustomer->setStatus(SubscriptionCustomer::PAYMENT_INVALID_STATUS);
            $this->subscriptionCustomerRepository->save($subscriptionCustomer);
            $this->messageManager->addError(__("Error creating payment"));
        }
    }

    public function subscriptionCanBeEnabledWithoutPayment($subscriptionCustomer)
    {
        $subscriptionPlan = $subscriptionCustomer->getSubscriptionPlan();
        if(!$subscriptionPlan->getPaymentRequiredForFree()){
            return true;
        }

        return false;
    }

    public function handleValidWithNoPayment($subscriptionCustomer)
    {
        $subscriptionCustomer->setStatus(SubscriptionCustomer::AUTORENEW_ON_STATUS);
        $this->subscriptionCustomerRepository->save($subscriptionCustomer);
    }


    //TODO: Refactor as currently will not be refected in history and should be able to be done through model
    private function createNewDevice($subscriptionCustomerData)
    {
        $subscriptionPlan = $this->subscriptionPlanRepository->getById($subscriptionCustomerData['subscription_plan_id']);

        $device = $this->subscriptionDeviceFactory->create();
        $device->setData([
            'serial_number' => null,
            'sku' => $subscriptionPlan->getDeviceSku(),
            'purchase_date' => Carbon::now(new \DateTimeZone('UTC'))->toDateTimeString(),
            'customer_id' => $subscriptionCustomerData['customer_id']
        ]);

        $device->save();

        return $device;
    }

}
