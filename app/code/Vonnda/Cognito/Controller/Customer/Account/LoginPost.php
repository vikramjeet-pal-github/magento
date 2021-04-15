<?php
/*
 * THIS CONTROLLER IS NOT CURRENTLY IN USE
 * Its purpose was to handle challenges that came from cognito, such as the email not being verified,
 * but half way through developing this it was decided all users would be automatically verified.
 * Hence the functionality is unfinished, but left here for potential future use, if needed.
 */

namespace Vonnda\Cognito\Controller\Customer\Account;

use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\State\UserLockedException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\Cookie\PhpCookieManager;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LoginPost extends \Magento\Customer\Controller\Account\LoginPost
{

    /** @var CustomerUrl */
    protected $customerUrl;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var PhpCookieManager */
    protected $cookieMetadataManager;

    /** @var CookieMetadataFactory */
    protected $cookieMetadataFactory;

    /**
     * Replaced some class variables with protected versions that are defined in the constructor through dependency injection,
     * replacing some private methods that were calling object manager directly
     * @param Context $context
     * @param Session $customerSession
     * @param AccountManagementInterface $customerAccountManagement
     * @param CustomerUrl $customerHelperData
     * @param Validator $formKeyValidator
     * @param AccountRedirect $accountRedirect
     * @param ScopeConfigInterface $scopeConfig
     * @param PhpCookieManager $cookieMetadataManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        AccountManagementInterface $customerAccountManagement,
        CustomerUrl $customerHelperData,
        Validator $formKeyValidator,
        AccountRedirect $accountRedirect,
        ScopeConfigInterface $scopeConfig,
        PhpCookieManager $cookieMetadataManager,
        CookieMetadataFactory $cookieMetadataFactory
    ) {
        $this->session = $customerSession;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->customerUrl = $customerHelperData;
        $this->formKeyValidator = $formKeyValidator;
        $this->accountRedirect = $accountRedirect;
        parent::__construct($context, $customerSession, $customerAccountManagement, $customerHelperData, $formKeyValidator, $accountRedirect);
        $this->scopeConfig = $scopeConfig;
        $this->cookieMetadataManager = $cookieMetadataManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
    }

    /** @inheritDoc */
    public function execute()
    {
		
        if ($this->session->isLoggedIn() || !$this->formKeyValidator->validate($this->getRequest())) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*/');
            return $resultRedirect;
        }
        if ($this->getRequest()->isPost()) {
            $login = $this->getRequest()->getPost('login');
            if (!empty($login['username']) && !empty($login['password'])) {
                try {
                    if ($this->session->getRequireCognitoValidation() === 1) {

                    }
                    $customer = $this->customerAccountManagement->authenticate($login['username'], $login['password']);
                    $this->session->setCustomerDataAsLoggedIn($customer);
                    $this->session->regenerateId();
                    if ($this->cookieMetadataManager->getCookie('mage-cache-sessid')) {
                        $metadata = $this->cookieMetadataFactory->createCookieMetadata();
                        $metadata->setPath('/');
                        $this->cookieMetadataManager->deleteCookie('mage-cache-sessid', $metadata);
                    }
                    $redirectUrl = $this->accountRedirect->getRedirectCookie();
                    if (!$this->scopeConfig->getValue('customer/startup/redirect_dashboard') && $redirectUrl) {
                        $this->accountRedirect->clearRedirectCookie();
                        $resultRedirect = $this->resultRedirectFactory->create();
                        // URL is checked to be internal in $this->_redirect->success()
                        $resultRedirect->setUrl($this->_redirect->success($redirectUrl));
                        return $resultRedirect;
                    }
                } catch (EmailNotConfirmedException $e) {
                    $value = $this->customerUrl->getEmailConfirmationUrl($login['username']);
                    $message = __('This account is not confirmed. <a href="%1">Click here</a> to resend confirmation email.', $value);
                } catch (UserLockedException $e) {
                    $message = __('The account sign-in was incorrect or your account is disabled temporarily. Please wait and try again later.');
                } catch (AuthenticationException $e) {
                    $message = __('The account sign-in was incorrect or your account is disabled temporarily. Please wait and try again later.');
                } catch (LocalizedException $e) {
                    $message = $e->getMessage();
                } catch (\Exception $e) {
                    // PA DSS violation: throwing or logging an exception here can disclose customer password
                    $this->messageManager->addError(__('An unspecified error occurred. Please contact us for assistance.'));
                } finally {
                    if (isset($message)) {
                        $this->messageManager->addError($message);
                        $this->session->setUsername($login['username']);
                    }
                }
            } else {
                $this->messageManager->addError(__('A login and a password are required.'));
            }
        }
        return $this->accountRedirect->getRedirect();
    }

}