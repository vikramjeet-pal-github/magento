<?php
namespace Vonnda\StripePayments\Api;

use Vonnda\Subscription\Api\SubscriptionPaymentRepositoryInterface;
use Vonnda\Subscription\Model\SubscriptionPaymentFactory;
use StripeIntegration\Payments\Model\ResourceModel\StripeCustomer\CollectionFactory;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\App\State;

class Service extends \StripeIntegration\Payments\Api\Service implements ServiceInterface
{

    /** REDECLARING PRIVATE PROPERTIES FROM PARENT CLASS */
    /** @var StripeCustomer */
    protected $stripeCustomer;

    /** DECLARING NEW PROPERTIES ADDED IN OVERRIDE */
    /** @var CollectionFactory */
    protected $collectionFactory;

    /** @var SubscriptionPaymentRepositoryInterface */
    protected $subscriptionPaymentRepository;

    /** @var UserContextInterface */
    protected $userContext;

    /** @var SubscriptionPaymentFactory */
    protected $subscriptionPaymentFactory;

    /** @var CustomerRepository */
    protected $customerRepo;

    /** @var State $state */
    protected $state;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \StripeIntegration\Payments\Helper\ExpressHelper $expressHelper,
        \StripeIntegration\Payments\Helper\Generic $stripeHelper,
        \StripeIntegration\Payments\Model\Config $config,
        \StripeIntegration\Payments\Model\StripeCustomer $stripeCustomer,
        \Magento\Framework\Webapi\ServiceInputProcessor $inputProcessor,
        \Magento\Quote\Api\Data\EstimateAddressInterfaceFactory $estimatedAddressFactory,
        \Magento\Quote\Api\ShippingMethodManagementInterface $shippingMethodManager,
        \Magento\Checkout\Api\ShippingInformationManagementInterface $shippingInformationManagement,
        \Magento\Checkout\Api\Data\ShippingInformationInterfaceFactory $shippingInformationFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Api\CartManagementInterface $quoteManagement,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \StripeIntegration\Payments\Model\PaymentIntent $paymentIntent,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \StripeIntegration\Payments\Model\MobileDetect $detect,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \StripeIntegration\Payments\Helper\Multishipping $multishippingHelper,
        \StripeIntegration\Payments\Helper\SetupIntent $setupIntent,
        \StripeIntegration\Payments\Helper\Klarna $klarnaHelper,
        CollectionFactory $collectionFactory,
        SubscriptionPaymentRepositoryInterface $subscriptionPaymentRepository,
        UserContextInterface $userContext,
        SubscriptionPaymentFactory $subscriptionPaymentFactory,
        CustomerRepository $customerRepo,
        State $state
    ) {
        parent::__construct(
            $logger,
            $scopeConfig,
            $storeManager,
            $urlBuilder,
            $eventManager,
            $cart,
            $checkoutHelper,
            $customerSession,
            $checkoutSession,
            $expressHelper,
            $stripeHelper,
            $config,
            $stripeCustomer,
            $inputProcessor,
            $estimatedAddressFactory,
            $shippingMethodManager,
            $shippingInformationManagement,
            $shippingInformationFactory,
            $quoteRepository,
            $quoteManagement,
            $orderSender,
            $productRepository,
            $paymentIntent,
            $dataObjectFactory,
            $detect,
            $registry,
            $priceCurrency,
            $multishippingHelper,
            $setupIntent,
            $klarnaHelper
        );
        $this->stripeCustomer = $stripeCustomer;
        $this->collectionFactory = $collectionFactory;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
        $this->userContext = $userContext;
        $this->subscriptionPaymentFactory = $subscriptionPaymentFactory;
        $this->customerRepo = $customerRepo;
        $this->state = $state;
    }

    /** {@inheritdoc} */
    public function getCustomerCards($customerId)
    {
        $collection = $this->collectionFactory->create();
        $stripeCustomer = $collection->addFieldToFilter('customer_id', $customerId)->getFirstItem();
        return $this->gatherCardData($stripeCustomer);
    }

    /** {@inheritdoc}*/
    public function getCustomer($customerId)
    {
        $collection = $this->collectionFactory->create();
        $stripeCustomer = $collection->addFieldToFilter('customer_id', $customerId)->getFirstItem();
        if ($stripeCustomer->getId()) {
            if ($stripeCustomer->getStripeId() == '' || $stripeCustomer->getStripeId() == null) {
                $customer = $this->customerRepo->getById($customerId);
                $this->stripeCustomer->createNewStripeCustomer($customer->getFirstname(), $customer->getLastname(), $customer->getEmail(), $customerId, [], $stripeCustomer->getId());
                return $this->stripeCustomer;
            }
            return $stripeCustomer;
        } else {
            $customer = $this->customerRepo->getById($customerId);
            $this->stripeCustomer->createNewStripeCustomer($customer->getFirstname(), $customer->getLastname(), $customer->getEmail(), $customerId);
            return $this->stripeCustomer;
        }
    }

    /** {@inheritdoc} */
    public function updateCustomer(\Vonnda\StripePayments\Api\Data\StripeCustomerInterface $stripeCustomer)
    {
        $customerId = $this->userContext->getUserId();
        $collection = $this->collectionFactory->create();
        $_stripeCustomer = $collection->addFieldToFilter('customer_id', $customerId)->getFirstItem();
        if ($_stripeCustomer) {
            $_stripeCustomer->setStripeId($stripeCustomer->getStripeId())->save();
        }
        return $_stripeCustomer;
    }

    /** {@inheritdoc} */
    public function getPaymentOptions($customerId)
    {
        $collection = $this->collectionFactory->create();
        $stripeCustomer = $collection->addFieldToFilter('customer_id', $customerId)->getFirstItem();
        $cards = $this->gatherCardData($stripeCustomer);
        $paymentOptions = [];
        foreach ($cards as $card) {
            $subscriptionPayment = $this->subscriptionPaymentFactory->create();
            $subscriptionPayment->setStripeCustomerId($stripeCustomer->getId())
                ->setExpirationDate($card['exp_month'] . "/" . $card['exp_year'])
                ->setPaymentCode($card['id']);
            $paymentOptions[] = $subscriptionPayment;
        }
        return $paymentOptions;
    }

    /**
     * Using emulation here since getCustomerCards checks if this request is from a logged in customer, or via the admin by checking AppState.
     * So we emulate the admin area to achieve the admin AppState.
     * @param \StripeIntegration\Payments\Model\StripeCustomer $stripeCustomer
     * @return array
     * @throws \Exception
     */
    protected function gatherCardData($stripeCustomer)
    {
        $cardData = [];
        if ($stripeCustomer) {
            $cards = $this->state->emulateAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML, [$stripeCustomer, 'getCustomerCards']);
        }
        if (isset($cards) && is_array($cards)) {
            foreach ($cards as $card) {
                $cardData[] = [
                    'id' => $card->id,
                    'exp_month' => $card->exp_month,
                    'exp_year' => $card->exp_year,
                    'last4' => $card->last4,
                    'brand' => $card->brand
                ];
            }
        }
        return $cardData;
    }

}