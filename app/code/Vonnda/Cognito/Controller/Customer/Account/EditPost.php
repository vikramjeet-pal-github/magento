<?php
namespace Vonnda\Cognito\Controller\Customer\Account;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Customer\Model\AuthenticationInterface;
use Magento\Customer\Model\Customer\Mapper;
use Magento\Customer\Model\EmailNotificationInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerExtractor;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\Exception\State\UserLockedException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Controller\AbstractAccount;
use Magento\Framework\Phrase;
use Vonnda\Cognito\Model\AuthService;
use Vonnda\TealiumTags\Helper\EditPost as TealiumHelper;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * Only functional changes were made in processChangeEmailRequest function
 * Class copied instead of extended since the changes were made in a private method and would require including the execute method
 * for the changes to be used, which would also require all the other private methods be added to scope. At which point most of the
 * class would have been copied anyway...
 * @see \Magento\Customer\Controller\Account\EditPost
 */
class EditPost extends AbstractAccount implements CsrfAwareActionInterface, HttpPostActionInterface
{

    /** Form code for data extractor*/
    const FORM_DATA_EXTRACTOR_CODE = 'customer_account_edit';

    /** @var AccountManagementInterface */
    protected $customerAccountManagement;

    /** @var CustomerRepositoryInterface */
    protected $customerRepository;

    /** @var Validator */
    protected $formKeyValidator;

    /** @var CustomerExtractor */
    protected $customerExtractor;

    /** @var Session */
    protected $session;

    /** @var AuthService */
    protected $authService;

    /** @var EmailNotificationInterface */
    private $emailNotification;

    /** @var AuthenticationInterface */
    private $authentication;

    /** @var Mapper */
    private $customerMapper;

    /** @var Escaper */
    private $escaper;

    /** @var TealiumHelper */
    protected $tealiumHelper;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param AccountManagementInterface $customerAccountManagement
     * @param CustomerRepositoryInterface $customerRepository
     * @param Validator $formKeyValidator
     * @param CustomerExtractor $customerExtractor
     * @param AuthService $authService
     * @param TealiumHelper $tealiumHelper
     * @param Escaper|null $escaper
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        AccountManagementInterface $customerAccountManagement,
        CustomerRepositoryInterface $customerRepository,
        Validator $formKeyValidator,
        CustomerExtractor $customerExtractor,
        AuthService $authService,
        TealiumHelper $tealiumHelper,
        ?Escaper $escaper = null
    ) {
        parent::__construct($context);
        $this->session = $customerSession;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->customerRepository = $customerRepository;
        $this->formKeyValidator = $formKeyValidator;
        $this->customerExtractor = $customerExtractor;
        $this->authService = $authService;
        $this->tealiumHelper = $tealiumHelper;
        $this->escaper = $escaper ?: ObjectManager::getInstance()->get(Escaper::class);
    }

    /**
     * @return AuthenticationInterface
     */
    private function getAuthentication()
    {
        if (!($this->authentication instanceof AuthenticationInterface)) {
            return ObjectManager::getInstance()->get(AuthenticationInterface::class);
        } else {
            return $this->authentication;
        }
    }

    /**
     * @return EmailNotificationInterface
     * @deprecated 100.1.0
     */
    private function getEmailNotification()
    {
        if (!($this->emailNotification instanceof EmailNotificationInterface)) {
            return ObjectManager::getInstance()->get(EmailNotificationInterface::class);
        } else {
            return $this->emailNotification;
        }
    }

    /** @inheritDoc */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/*/edit');
        return new InvalidRequestException($resultRedirect, [new Phrase('Invalid Form Key. Please refresh the page.')]);
    }

    /** @inheritDoc */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return null;
    }

    /**
     * Change customer email or password action
     * @return Redirect
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\SessionException
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $validFormKey = $this->formKeyValidator->validate($this->getRequest());
        if ($validFormKey && $this->getRequest()->isPost()) {
            $currentCustomerDataObject = $this->getCustomerDataObject($this->session->getCustomerId());
            $customerCandidateDataObject = $this->populateNewCustomerDataObject($this->_request, $currentCustomerDataObject);
            try {
                // changing password first because otherwise the authentication before changing the password would have failed due to email already being different
                $isPasswordChanged = $this->changeCustomerPassword($currentCustomerDataObject->getEmail()); // whether a customer enabled change password option
                $this->processChangeEmailRequest($currentCustomerDataObject); // whether a customer enabled change email option
                $this->customerRepository->save($customerCandidateDataObject);
                $this->getEmailNotification()->credentialsChanged($customerCandidateDataObject, $currentCustomerDataObject->getEmail(), $isPasswordChanged);
                $this->dispatchSuccessEvent($customerCandidateDataObject);
                $this->messageManager->addSuccess(__('You saved the account information.'));
                return $resultRedirect->setPath('customer/account');
            } catch (InvalidEmailOrPasswordException $e) {
                $this->messageManager->addErrorMessage($this->escaper->escapeHtml($e->getMessage()));
            } catch (UserLockedException $e) {
                $message = __('The account sign-in was incorrect or your account is disabled temporarily. Please wait and try again later.');
                $this->session->logout();
                $this->session->start();
                $this->messageManager->addError($message);
                return $resultRedirect->setPath('customer/account/login');
            } catch (InputException $e) {
                $this->messageManager->addErrorMessage($this->escaper->escapeHtml($e->getMessage()));
                foreach ($e->getErrors() as $error) {
                    $this->messageManager->addErrorMessage($this->escaper->escapeHtml($error->getMessage()));
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('We can\'t save the customer.'));
            }
            $this->session->setCustomerFormData($this->getRequest()->getPostValue());
        }
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/*/edit');
        return $resultRedirect;
    }

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customerCandidateDataObject
     * @return void
     */
    private function dispatchSuccessEvent(\Magento\Customer\Api\Data\CustomerInterface $customerCandidateDataObject)
    {
        $this->_eventManager->dispatch('customer_account_edited', ['email' => $customerCandidateDataObject->getEmail()]);
    }

    /**
     * @param int $customerId
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCustomerDataObject($customerId)
    {
        return $this->customerRepository->getById($customerId);
    }

    /**
     * Create Data Transfer Object of customer candidate
     * @param RequestInterface $inputData
     * @param \Magento\Customer\Api\Data\CustomerInterface $currentCustomerData
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    private function populateNewCustomerDataObject(RequestInterface $inputData, \Magento\Customer\Api\Data\CustomerInterface $currentCustomerData)
    {
        $attributeValues = $this->getCustomerMapper()->toFlatArray($currentCustomerData);
        $customerDto = $this->customerExtractor->extract(self::FORM_DATA_EXTRACTOR_CODE, $inputData, $attributeValues);
        $customerDto->setId($currentCustomerData->getId());
        if (!$customerDto->getAddresses()) {
            $customerDto->setAddresses($currentCustomerData->getAddresses());
        }
        if (!$inputData->getParam('change_email')) {
            $customerDto->setEmail($currentCustomerData->getEmail());
        }
        $uuid = $currentCustomerData->getCustomAttribute('cognito_uuid');
        $customerUid = ($uuid && $uuid->getValue()) ? $uuid->getValue() : "";
        $customerDto->setCustomAttribute('cognito_uuid', $customerUid);
        return $customerDto;
    }

    /**
     * @param string $email
     * @return boolean
     * @throws InvalidEmailOrPasswordException|InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function changeCustomerPassword($email)
    {
        $isPasswordChanged = false;
        if ($this->getRequest()->getParam('change_password')) {
            $currPass = $this->getRequest()->getPost('current_password');
            $newPass = $this->getRequest()->getPost('password');
            $confPass = $this->getRequest()->getPost('password_confirmation');
            if ($newPass != $confPass) {
                throw new InputException(__('Password confirmation doesn\'t match entered password.'));
            }
            $isPasswordChanged = $this->customerAccountManagement->changePassword($email, $currPass, $newPass);
        }
        if($isPasswordChanged){
            $this->tealiumHelper->createEditPasswordSuccessEvent();
        }

        return $isPasswordChanged;
    }

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $currentCustomerDataObject
     * @return void
     * @throws InvalidEmailOrPasswordException
     * @throws LocalizedException
     */
    private function processChangeEmailRequest(\Magento\Customer\Api\Data\CustomerInterface $currentCustomerDataObject)
    {
        if ($this->getRequest()->getParam('change_email')) {
            /** Checking the current password entered in the form is correct. Failure throws an error, which we then throw the error used by the core. */
            try {
                if(!$this->getRequest()->getParam('change_password')) {
                    // if the password was just changed, the authentication here is not needed again
                    $this->authService->authenticate($currentCustomerDataObject->getEmail(), $this->getRequest()->getPost('current_password'));
                }
            } catch (\Exception $e) {
                $errorMessage = "Incorrect password. Please verify and try again.";
                $this->tealiumHelper->createEditEmailFailureEvent($errorMessage);
                throw new InvalidEmailOrPasswordException(__($errorMessage));
            }
            /**
             * Confirming the email is not in use. Must check cognito due to the possibility of a user existing in cognito and not magento.
             * Tested before this check was implemented, and the exception thrown below is the same as what magento throws if the email already exists in magento.
             * Update: Error message updated as per the client requirements.
             */
            if ($this->authService->adminGetUser($this->getRequest()->getParam('email')) !== false) {
                $errorMessage = 'This email is already associated with an account.';
                $this->tealiumHelper->createEditEmailFailureEvent($errorMessage);
                throw new LocalizedException(__($errorMessage));
            }
            $this->authService->updateUserAttributes($currentCustomerDataObject->getEmail(), ['email' => $this->getRequest()->getParam('email'), 'email_verified' => 'true']);
            $this->tealiumHelper->createEditEmailEvent($currentCustomerDataObject->getEmail(), $this->getRequest()->getParam('email'));
        }
    }

    /**
     * @return Mapper
     * @deprecated 100.1.3
     */
    private function getCustomerMapper()
    {
        if ($this->customerMapper === null) {
            $this->customerMapper = ObjectManager::getInstance()->get(Mapper::class);
        }
        return $this->customerMapper;
    }

}