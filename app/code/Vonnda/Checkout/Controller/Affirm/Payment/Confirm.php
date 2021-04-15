<?php
namespace Vonnda\Checkout\Controller\Affirm\Payment;

use Magento\Framework\App\Action\Context;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Checkout\Model\Session;
use Astound\Affirm\Model\Checkout;
use Magento\Framework\App\ResourceConnection;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Vonnda\Cognito\Model\AuthService;
use Vonnda\Checkout\Helper\Data as CheckoutHelper;
use Psr\Log\LoggerInterface;

class Confirm extends \Astound\Affirm\Controller\Payment\Confirm
{

    /** @var CartRepositoryInterface */
    protected $quoteRepository;

    /** @var CustomerInterfaceFactory */
    protected $customerFactory;

    /** @var AccountManagementInterface */
    protected $accountManager;

    /** @var CustomerSession */
    protected $customerSession;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var AuthService */
    protected $authService;

    /** @var CheckoutHelper */
    protected $checkoutHelper;

    /** @var LoggerInterface */
    protected $logger;

    /** @var ResourceConnection */
    protected $connectionPool;

    public function __construct(
        Context $context,
        CartManagementInterface $quoteManager,
        Session $checkoutSession,
        Checkout $checkout,
        CartRepositoryInterface $quoteRepository,
        CustomerInterfaceFactory $customerFactory,
        AccountManagementInterface $accountManager,
        CustomerSession $customerSession,
        ScopeConfigInterface $scopeConfig,
        AuthService $authService,
        CheckoutHelper $checkoutHelper,
        LoggerInterface $logger,
        ResourceConnection $connectionPool
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->customerFactory = $customerFactory;
        $this->accountManager = $accountManager;
        $this->customerSession = $customerSession;
        $this->scopeConfig = $scopeConfig;
        $this->authService = $authService;
        $this->checkoutHelper = $checkoutHelper;
        $this->logger = $logger;
        $this->connectionPool = $connectionPool;
        parent::__construct(
            $context,
            $quoteManager,
            $checkoutSession,
            $checkout
        );
    }

    public function execute()
    {
        $email = $this->quote->getBillingAddress()->getEmail();
        $password = $this->customerSession->getPassword();
        $sendPasswordReset = false;
        if (!$this->customerSession->isLoggedIn()) {
            $salesConnection = $this->connectionPool->getConnection('sales')->beginTransaction();
            $checkoutConnection = $this->connectionPool->getConnection('checkout')->beginTransaction();
            $customerConnection = $this->connectionPool->getConnection('customer')->beginTransaction();
            try {
                $this->customerSession->unsPassword();
                $customer = $this->checkoutHelper->getCustomerIfExists($email);
                if ($customer === false && !$this->quote->getGiftOrder()) {
                    if ($this->checkoutHelper->isDeviceInCart()) {
                        $customer = $this->createCustomer($email, $password);
                        $this->customerSession->setCustomerDataAsLoggedIn($customer);
                        if (!$this->scopeConfig->getValue('aw_osc/general/require_password', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
                            $sendPasswordReset = true;
                        }
                    }
                }
                if (is_object($customer)) { // check customer again, in the case where customer was initially false and an account was created
                    $email = $customer->getEmail();
                    $this->quote->setCustomerEmail($email)->setCustomer($customer)->setCustomerId($customer->getId())->setCustomerIsGuest(0);
                    $billingAddress = $this->quote->getBillingAddress();
                    $billingAddress->setCustomerId($customer->getId())->save();
                    $this->quoteRepository->save($this->quote);
                } else {
                    $this->quote->setCustomerId(null)
                        ->setCustomerEmail($email)
                        ->setCustomerFirstname($this->quote->getBillingAddress()->getFirstname())
                        ->setCustomerLastname($this->quote->getBillingAddress()->getLastname())
                        ->setCustomerMiddlename($this->quote->getBillingAddress()->getMiddlename())
                        ->setCustomerIsGuest(true)
                        ->setCustomerGroupId(\Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID);
                    $this->quoteRepository->save($this->quote);
                }
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, $e->getMessage());
                $salesConnection->rollBack();
                $checkoutConnection->rollBack();
                $customerConnection->rollBack();
                $this->_redirect('checkout');
                return;
            }
            $salesConnection->commit();
            $checkoutConnection->commit();
            $customerConnection->commit();
            try {
                if ($sendPasswordReset) {
                    $this->authService->forgotPassword($email);
                }
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }
        return parent::execute();
    }

    protected function createCustomer($email, $password)
    {
        $customer = $this->customerFactory->create();
        $customer->setWebsiteId($this->quote->getWebsiteId());
        $customer->setEmail($email); 
        $customer->setFirstname($this->quote->getBillingAddress()->getFirstname());
        $customer->setLastname($this->quote->getBillingAddress()->getLastname());
        $customer = $this->accountManager->createAccount($customer, $password);
        return $customer;
    }

}