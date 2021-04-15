<?php
namespace Vonnda\Cognito\Model\Customer;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\ValidationResultsInterfaceFactory;
use Magento\Customer\Helper\View as CustomerViewHelper;
use Magento\Customer\Model\Config\Share as ConfigShare;
use Magento\Customer\Model\Customer as CustomerModel;
use Magento\Customer\Model\Customer\CredentialsValidator;
use Magento\Customer\Model\Metadata\Validator;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObjectFactory as ObjectFactory;
use Magento\Framework\Encryption\EncryptorInterface as Encryptor;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Framework\Exception\State\InvalidTransitionException;
use Magento\Framework\Exception\State\UserLockedException;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Math\Random;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\StringUtils as StringHelper;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface as PsrLogger;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Session\SaveHandlerInterface;
use Magento\Customer\Model\ResourceModel\Visitor\CollectionFactory;
use Magento\Customer\Model\EmailNotificationInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\AccountConfirmation;
use Vonnda\Cognito\Model\AuthService;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\AuthenticationInterface;
use Magento\Framework\Exception\MailException;
use Magento\Customer\Model\CustomerExtractor;
use Magento\Framework\App\RequestInterface;
use Vonnda\TealiumTags\Helper\AccountManagement as TealiumHelper;

/**
 * Handle various customer account actions
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class AccountManagement extends \Magento\Customer\Model\AccountManagement
{

    /** @var CustomerFactory */
    protected $customerFactory;

    /** @var \Magento\Customer\Api\Data\ValidationResultsInterfaceFactory */
    protected $validationResultsDataFactory;

    /** @var ManagerInterface */
    protected $eventManager;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var Random */
    protected $mathRandom;

    /** @var Validator */
    protected $validator;

    /** @var AddressRepositoryInterface */
    protected $addressRepository;

    /** @var CustomerMetadataInterface */
    protected $customerMetadataService;

    /** @var Encryptor */
    protected $encryptor;

    /** @var CustomerRegistry */
    protected $customerRegistry;

    /** @var ConfigShare */
    protected $configShare;

    /** @var CustomerRepositoryInterface */
    protected $customerRepository;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var TransportBuilder */
    protected $transportBuilder;

    /** @var SessionManagerInterface */
    protected $sessionManager;

    /** @var SaveHandlerInterface */
    protected $saveHandler;

    /** @var CollectionFactory */
    protected $visitorCollectionFactory;

    /** @var EmailNotificationInterface */
    protected $emailNotification;

    /** @var \Magento\Eav\Model\Validator\Attribute\Backend */
    protected $eavValidator;

    /** @var CredentialsValidator */
    protected $credentialsValidator;

    /** @var DateTimeFactory */
    protected $dateTimeFactory;

    /** @var AccountConfirmation */
    protected $accountConfirmation;

    /** @var SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /** @var AuthService */
    protected $authService;

    /** @var CustomerSession */
    protected $customerSession;

    /** @var AuthenticationInterface */
    protected $authInterface;

    /** @var CustomerExtractor */
    protected $customerExtractor;

    /** @var RequestInterface */
    protected $request;

    /** @var TealiumHelper*/
    protected $tealiumHelper;

    /** @var string */
    protected $uuid;

    /**
     * @param CustomerFactory $customerFactory
     * @param ManagerInterface $eventManager
     * @param StoreManagerInterface $storeManager
     * @param Random $mathRandom
     * @param Validator $validator
     * @param ValidationResultsInterfaceFactory $validationResultsDataFactory
     * @param AddressRepositoryInterface $addressRepository
     * @param CustomerMetadataInterface $customerMetadataService
     * @param CustomerRegistry $customerRegistry
     * @param PsrLogger $logger
     * @param Encryptor $encryptor
     * @param ConfigShare $configShare
     * @param StringHelper $stringHelper
     * @param CustomerRepositoryInterface $customerRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param TransportBuilder $transportBuilder
     * @param DataObjectProcessor $dataProcessor
     * @param Registry $registry
     * @param CustomerViewHelper $customerViewHelper
     * @param DateTime $dateTime
     * @param CustomerModel $customerModel
     * @param ObjectFactory $objectFactory
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param AuthService $authService
     * @param CustomerSession $customerSession
     * @param AuthenticationInterface $authInterface
     * @param CustomerExtractor $customerExtractor
     * @param RequestInterface $request
     * @param TealiumHelper $tealiumHelper
     * @param CredentialsValidator|null $credentialsValidator
     * @param DateTimeFactory|null $dateTimeFactory
     * @param AccountConfirmation|null $accountConfirmation
     * @param SessionManagerInterface|null $sessionManager
     * @param SaveHandlerInterface|null $saveHandler
     * @param CollectionFactory|null $visitorCollectionFactory
     * @param SearchCriteriaBuilder|null $searchCriteriaBuilder
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        CustomerFactory $customerFactory,
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        Random $mathRandom,
        Validator $validator,
        ValidationResultsInterfaceFactory $validationResultsDataFactory,
        AddressRepositoryInterface $addressRepository,
        CustomerMetadataInterface $customerMetadataService,
        CustomerRegistry $customerRegistry,
        PsrLogger $logger,
        Encryptor $encryptor,
        ConfigShare $configShare,
        StringHelper $stringHelper,
        CustomerRepositoryInterface $customerRepository,
        ScopeConfigInterface $scopeConfig,
        TransportBuilder $transportBuilder,
        DataObjectProcessor $dataProcessor,
        Registry $registry,
        CustomerViewHelper $customerViewHelper,
        DateTime $dateTime,
        CustomerModel $customerModel,
        ObjectFactory $objectFactory,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        AuthService $authService,
        CustomerSession $customerSession,
        AuthenticationInterface $authInterface,
        CustomerExtractor $customerExtractor,
        RequestInterface $request,
        TealiumHelper $tealiumHelper,
        CredentialsValidator $credentialsValidator = null,
        DateTimeFactory $dateTimeFactory = null,
        AccountConfirmation $accountConfirmation = null,
        SessionManagerInterface $sessionManager = null,
        SaveHandlerInterface $saveHandler = null,
        CollectionFactory $visitorCollectionFactory = null,
        SearchCriteriaBuilder $searchCriteriaBuilder = null
    ) {
        $this->customerFactory = $customerFactory;
        $this->eventManager = $eventManager;
        $this->storeManager = $storeManager;
        $this->mathRandom = $mathRandom;
        $this->validator = $validator;
        $this->validationResultsDataFactory = $validationResultsDataFactory;
        $this->addressRepository = $addressRepository;
        $this->customerMetadataService = $customerMetadataService;
        $this->customerRegistry = $customerRegistry;
        $this->logger = $logger;
        $this->encryptor = $encryptor;
        $this->configShare = $configShare;
        $this->stringHelper = $stringHelper;
        $this->customerRepository = $customerRepository;
        $this->scopeConfig = $scopeConfig;
        $this->transportBuilder = $transportBuilder;
        $this->dataProcessor = $dataProcessor;
        $this->registry = $registry;
        $this->customerViewHelper = $customerViewHelper;
        $this->dateTime = $dateTime;
        $this->customerModel = $customerModel;
        $this->objectFactory = $objectFactory;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
        $this->tealiumHelper = $tealiumHelper;
        $this->credentialsValidator = $credentialsValidator ?: ObjectManager::getInstance()->get(CredentialsValidator::class);
        $this->dateTimeFactory = $dateTimeFactory ?: ObjectManager::getInstance()->get(DateTimeFactory::class);
        $this->accountConfirmation = $accountConfirmation ?: ObjectManager::getInstance()->get(AccountConfirmation::class);
        $this->sessionManager = $sessionManager ?: ObjectManager::getInstance()->get(SessionManagerInterface::class);
        $this->saveHandler = $saveHandler ?: ObjectManager::getInstance()->get(SaveHandlerInterface::class);
        $this->visitorCollectionFactory = $visitorCollectionFactory ?: ObjectManager::getInstance()->get(CollectionFactory::class);
        $this->searchCriteriaBuilder = $searchCriteriaBuilder ?: ObjectManager::getInstance()->get(SearchCriteriaBuilder::class);
        parent::__construct(
            $customerFactory,
            $eventManager,
            $storeManager,
            $mathRandom,
            $validator,
            $validationResultsDataFactory,
            $addressRepository,
            $customerMetadataService,
            $customerRegistry,
            $logger,
            $encryptor,
            $configShare,
            $stringHelper,
            $customerRepository,
            $scopeConfig,
            $transportBuilder,
            $dataProcessor,
            $registry,
            $customerViewHelper,
            $dateTime,
            $customerModel,
            $objectFactory,
            $extensibleDataObjectConverter,
            $credentialsValidator,
            $dateTimeFactory,
            $accountConfirmation,
            $sessionManager,
            $saveHandler,
            $visitorCollectionFactory,
            $searchCriteriaBuilder
        );
        // Additional dependencies added
        $this->authService = $authService;
        $this->customerSession = $customerSession;
        $this->authInterface = $authInterface;
        $this->customerExtractor = $customerExtractor;
        $this->request = $request;
    }

    /**
     * createAccounts would be more accurate, but inheritance and all that.
     * This override creates both cognito and magento accounts, using the uuid of the cognito user in the password hash of the magento user.
     * Magento natively allows for the password field to be null when creating a customer. But, since the password now actually falls to cognito, that's not the case.
     * So in any instance where the password is null, one needs to be generated.
     * @inheritdoc
     */
    public function createAccount(CustomerInterface $customer, $password = null, $redirectUrl = '')
    {
        if ($password === null) {
            $password = $this->generateTempPassword();
        }
        $this->checkPasswordStrength($password);
        $customerEmail = strtolower($customer->getEmail());
        $customer->setEmail($customerEmail);
        try {
            $this->credentialsValidator->checkPasswordDifferentFromEmail($customerEmail, $password);
        } catch (InputException $e) {
            throw new LocalizedException(__("The password can't be the same as the email address. Create a new password and try again."));
        }
        try {
            $uuid = $this->authService->registerUser($customerEmail, $password, [
                'given_name' => $customer->getFirstname(),
                'family_name' => $customer->getLastname()
            ]);
            $hash = $this->createPasswordHash($uuid.'-mlk2019');
            $customer = $this->createAccountWithPasswordHash($customer, $hash, $redirectUrl);
            $customerModel = $this->customerFactory->create();
            $customerModel->getResource()->load($customerModel, $customer->getId());
            $customerModel->setData('cognito_uuid', $uuid)
                ->setAttributeSetId(CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER);
            $customerModel->getResource()->save($customerModel);
            return $customer;
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * Created specifically for the cases where a user exists in cognito, but not magento. If the user attempts to login, passes cognito auth
     * but the user lookup fails, the user is created on the spot through this function and continues to log them in
     * @param CustomerInterface $customer
     * @return CustomerInterface
     * @throws LocalizedException
     */
    public function createMagentoAccount(CustomerInterface $customer, $uuid)
    {
        try {
            $hash = $this->createPasswordHash($uuid.'-mlk2019');
            $customer = $this->createAccountWithPasswordHash($customer, $hash, false);
            $customerModel = $this->customerFactory->create();
            $customerModel->getResource()->load($customerModel, $customer->getId());
            $customerModel->setData('cognito_uuid', $uuid)
                ->setAttributeSetId(CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER);
            $customerModel->getResource()->save($customerModel);
            return $customer;
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /** @inheritdoc */
    public function authenticate($username, $password)
    {
		\Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)->debug('message21');
        try {
            $customer = $this->customerRepository->get($username);
            $userTokens = $this->authService->authenticate($customer->getEmail(), $password);
        } catch (EmailNotConfirmedException $e) {
            $this->customerSession->setRequireCognitoValidation(1);
            throw $e;
        } catch (NoSuchEntityException $e) {
			\Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)->debug('nosuch');
            throw new InvalidEmailOrPasswordException(__('Invalid login or password.'));
        }catch (UserLockedException $e) {
			\Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)->debug('locked');
			$lockedDate = date('Y-m-d', strtotime('+1 years'));
			//Set Lockout flag from Cognito
			$customerModel = $this->customerFactory->create();
            $customerModel->getResource()->load($customerModel, $customer->getId());
            $customerModel->setData('lock_expires', $lockedDate);
            $customerModel->getResource()->save($customerModel);
			//Set Lockout flag from Cognito
            throw new UserLockedException(__('Account locked.'));
        } catch (\Exception $e) {
            throw new LocalizedException(__('An error occurred while trying to authenticate. Please try again.'));
        }
        $customer = $this->getCustomer($username, $userTokens['AccessToken']);
        $customerId = $customer->getId();
        $magePassword = $this->uuid.'-mlk2019';
		
		\Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)->debug($magePassword);
        
		if ($customerId !== null) {
            if ($this->authInterface->isLocked($customerId)) {
				//Remove Lockout flag from Cognito
				$customerModel = $this->customerFactory->create();
				$customerModel->getResource()->load($customerModel, $customer->getId());
				$customerModel->setData('lock_expires', '');
				$customerModel->getResource()->save($customerModel);
				//Remove Lockout flag from Cognito
                //throw new UserLockedException(__('The account is locked.'));
            }
            try {
                $this->authInterface->authenticate($customerId, $magePassword);
				
            } catch (InvalidEmailOrPasswordException $e) {
                throw new InvalidEmailOrPasswordException(__('Invalid login or password.'));
            }
        }
        $customerModel = $this->customerFactory->create()->updateData($customer);
        $this->eventManager->dispatch('customer_customer_authenticated', ['model' => $customerModel, 'password' => $magePassword]);
        $this->eventManager->dispatch('customer_data_object_login', ['customer' => $customer]);
        return $customer;
    }

    /** @inheritdoc */
    public function resendConfirmation($email, $websiteId = null, $redirectUrl = '')
    {
        $customer = $this->customerRepository->get($email, $websiteId);
        if (!$customer->getConfirmation()) {
            throw new InvalidTransitionException(__("Confirmation isn't needed."));
        }
        try {
            // $this->getEmailNotification()->newAccount($customer, self::NEW_ACCOUNT_EMAIL_CONFIRMATION, $redirectUrl, $this->storeManager->getStore()->getId());
            $this->authService->sendValidationEmail($email);
        } catch (MailException $e) {
            // If we are not able to send a new account email, this should be ignored
            $this->logger->critical($e);
        }
    }

    /**
     * Activate a customer account using a key that was sent in a confirmation email.
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param string $confirmationKey
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws InputException|InputMismatchException|InvalidTransitionException|LocalizedException|NoSuchEntityException
     */
    protected function activateCustomer($customer, $confirmationKey)
    {
        // check if customer is inactive
        if (!$customer->getConfirmation()) {
            throw new InvalidTransitionException(__('The account is already active.'));
        }
        if ($customer->getConfirmation() !== $confirmationKey) {
            throw new InputMismatchException(__('The confirmation token is invalid. Verify the token and try again.'));
        }
        $customer->setConfirmation(null);
        $this->customerRepository->save($customer);
        // $this->getEmailNotification()->newAccount($customer, 'confirmed', '', $this->storeManager->getStore()->getId());
        return $customer;
    }

    /**
     * This is called in:
     * Magento\Customer\Controller\Account\ForgotPasswordPost
     * Magento\Customer\Controller\Adminhtml\Index\ResetPassword
     * This override handles both frontend and admin reset password
     * @inheritdoc
     */
    public function initiatePasswordReset($email, $template, $websiteId = null)
    {
        $customer = $this->customerRepository->get($email);
        $this->tealiumHelper->createForgotPasswordLinkCreatedEvent($customer->getEmail());
        $this->authService->forgotPassword($customer->getEmail());
        return true;
    }

    /**
     * Created for use in the Cognito CustomerTokenService model, to retrieve the user account and use the customer ID to issue an API token
     * @param string $username
     * @param string $accessToken
     * @return CustomerInterface
     * @throws LocalizedException
     */
    public function getCustomer($username, $accessToken)
    {
        try {
            $customer = $this->customerRepository->get($username);
            $uuid = $customer->getCustomAttribute('cognito_uuid');
            $customerSecure = $this->customerRegistry->retrieveSecureData($customer->getId());
            /* uuid being null seems to be an issue with the attribute being unset on save, so this is a bandaid until its figured out */
            if ($uuid == null) {
                $user = $this->authService->getUser($accessToken);
                $this->uuid = $user->get('Username');
                $customer->setCustomAttribute('cognito_uuid', $this->uuid);
                $customerModel = $this->customerFactory->create();
                $customerModel->getResource()->load($customerModel, $customer->getId());
                $customerModel->setData('cognito_uuid', $this->uuid)
                    ->setAttributeSetId(CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER);
                $customerModel->getResource()->save($customerModel);
            } else {
                $this->uuid = $uuid->getValue();
            }
            /* passwordHash being null is an artifact from user imports, so we set it here after the user has successfully authenticated with cognito to make sure they can log in */
            if ($customerSecure->getPasswordHash() == null) {
                $hash = $this->createPasswordHash($this->uuid.'-mlk2019');
                $customer = $this->customerRepository->save($customer, $hash);
            }
        } catch (NoSuchEntityException $e) { // customer exists in cognito and passed authentication, but has no magento user
            $user = $this->authService->getUser($accessToken);
            $this->uuid = $user->get('Username');
            $this->request->setPostValue('login', null);
            $map = ['given_name' => 'firstname', 'family_name' => 'lastname', 'email' => 'email'];
            foreach ($user->get('UserAttributes') as $attr) {
                if (array_key_exists($attr['Name'], $map)) {
                    if ($attr['Name'] == 'email') {
                        $this->request->setPostValue($map[$attr['Name']], strtolower($attr['Value']));
                    } else {
                        $this->request->setPostValue($map[$attr['Name']], $attr['Value']);
                    }
                }
            }
            $customer = $this->customerExtractor->extract('customer_account_create', $this->request);
            if ($customer->getFirstname() == null || trim($customer->getFirstname()) == '') {
                $customer->setFirstname('Firstname');
            }
            if ($customer->getLastname() == null || trim($customer->getLastname()) == '') {
                $customer->setLastname('Lastname');
            }
            $customer = $this->createMagentoAccount($customer, $this->uuid);
        }
        return $customer;
    }

    /** @inheritdoc */
    public function isEmailAvailable($customerEmail, $websiteId = null)
    {
        try {
            if ($websiteId === null) {
                $websiteId = $this->storeManager->getStore()->getWebsiteId();
            }
            if(empty($customerEmail)) {
                return true;
            }
            $this->customerRepository->get($customerEmail, $websiteId);
            return false;
        } catch (NoSuchEntityException $e) {
            // no user in magento, but check if a cognito user exists
            if ($this->authService->adminGetUser($customerEmail) !== false) {
                return false;
            }
            return true;
        }
    }

    /**
     * @param $customerEmail
     * @param null $websiteId
     * @return bool|CustomerInterface
     * @throws LocalizedException
     */
    public function getCustomerIfExists($customerEmail, $websiteId = null)
    {
        try {
            if ($websiteId === null) {
                $websiteId = $this->storeManager->getStore()->getWebsiteId();
            }
            return $this->customerRepository->get($customerEmail, $websiteId);
        } catch (NoSuchEntityException $e) {
            $user = $this->authService->adminGetUser($customerEmail);
            if ($user !== false) {
                $this->uuid = $user->get('Username');
                $this->request->setPostValue('login', null);
                $map = ['given_name' => 'firstname', 'family_name' => 'lastname', 'email' => 'email'];
                foreach ($user->get('UserAttributes') as $attr) {
                    if (array_key_exists($attr['Name'], $map)) {
                        $this->request->setPostValue($map[$attr['Name']], $attr['Value']);
                    }
                }
                $customer = $this->customerExtractor->extract('customer_account_create', $this->request);
                if ($websiteId != null) {
                    $customer->setWebsiteId($websiteId);
                }
                if ($customer->getFirstname() == null || trim($customer->getFirstname()) == '') {
                    $customer->setFirstname('Firstname');
                }
                if ($customer->getLastname() == null || trim($customer->getLastname()) == '') {
                    $customer->setLastname('Lastname');
                }
                return $this->createMagentoAccount($customer, $this->uuid);
            }
            return false;
        }
    }

    /**
     * @param string $customerEmail
     * @param null $websiteId
     * @return bool|CustomerModel
     */
    public function getMagentoCustomer($customerEmail, $websiteId = null)
    {
        try {
            if ($websiteId === null) {
                $websiteId = $this->storeManager->getStore()->getWebsiteId();
            }
            return $this->customerRegistry->retrieveByEmail($customerEmail, $websiteId);
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * @param $customerEmail
     * @param null $websiteId
     * @return bool|CustomerInterface
     * @throws LocalizedException|\Exception
     */
    public function getCognitoCustomer($customerEmail, $websiteId = null)
    {
        $user = $this->authService->adminGetUser($customerEmail);
        if ($user !== false) {
            $this->uuid = $user->get('Username');
            $this->request->setPostValue('login', null);
            $map = ['given_name' => 'firstname', 'family_name' => 'lastname', 'email' => 'email'];
            foreach ($user->get('UserAttributes') as $attr) {
                if (array_key_exists($attr['Name'], $map)) {
                    $this->request->setPostValue($map[$attr['Name']], $attr['Value']);
                }
            }
            $customer = $this->customerExtractor->extract('customer_account_create', $this->request);
            if ($websiteId != null) {
                $customer->setWebsiteId($websiteId);
            }
            if ($customer->getFirstname() == null || trim($customer->getFirstname()) == '') {
                $customer->setFirstname('Firstname');
            }
            if ($customer->getLastname() == null || trim($customer->getLastname()) == '') {
                $customer->setLastname('Lastname');
            }
            return $this->createMagentoAccount($customer, $this->uuid);
        }
        return false;
    }

    /**
     * @param string $customerEmail
     * @param null $websiteId
     * @return bool
     */
    public function doesMagentoCustomerExist($customerEmail, $websiteId = null)
    {
        try {
            if ($websiteId === null) {
                $websiteId = $this->storeManager->getStore()->getWebsiteId();
            }
            $this->customerRegistry->retrieveByEmail($customerEmail, $websiteId);
            return true;
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * @param $customerEmail
     * @return bool
     * @throws \Exception
     */
    public function doesCognitoUserExist($customerEmail)
    {
        $user = $this->authService->adminGetUser($customerEmail);
        return $user !== false;
    }

    /** @inheritDoc */
    protected function sendEmailConfirmation(CustomerInterface $customer, $redirectUrl)
    {
        try {
            // adding this check to make it possible to not send the email
            if ($redirectUrl !== false) {
                $hash = $this->customerRegistry->retrieveSecureData($customer->getId())->getPasswordHash();
                $templateType = self::NEW_ACCOUNT_EMAIL_REGISTERED;
                if ($this->isConfirmationRequired($customer) && $hash != '') {
                    $templateType = self::NEW_ACCOUNT_EMAIL_CONFIRMATION;
                } elseif ($hash == '') {
                    $templateType = self::NEW_ACCOUNT_EMAIL_REGISTERED_NO_PASSWORD;
                }
                $this->getEmailNotification()->newAccount($customer, $templateType, $redirectUrl, $customer->getStoreId());
            }
        } catch (MailException $e) {
            // If we are not able to send a new account email, this should be ignored
            $this->logger->critical($e);
        } catch (\UnexpectedValueException $e) {
            $this->logger->error($e);
        }
    }

    /**
     * NO CHANGES
     * Overridden due to calling a private method
     * @inheritdoc
     */
    public function changePassword($email, $currentPassword, $newPassword)
    {
        try {
            $customer = $this->customerRepository->get($email);
        } catch (NoSuchEntityException $e) {
            throw new InvalidEmailOrPasswordException(__('Invalid login or password.'));
        }
        return $this->changePasswordForCustomer($customer, $currentPassword, $newPassword);
    }

    /**
     * NO CHANGES
     * Overridden due to calling a private method
     * @inheritdoc
     */
    public function changePasswordById($customerId, $currentPassword, $newPassword)
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
        } catch (NoSuchEntityException $e) {
            throw new InvalidEmailOrPasswordException(__('Invalid login or password.'));
        }
        return $this->changePasswordForCustomer($customer, $currentPassword, $newPassword);
    }

    /** @inheritDoc */
    private function changePasswordForCustomer($customer, $currentPassword, $newPassword)
    {
        try {
            $userTokens = $this->authService->authenticate($customer->getEmail(), $currentPassword);
        } catch (\Exception $e) {
            throw new InvalidEmailOrPasswordException(__("The password doesn't match this account. Verify the password and try again."));
        }
        $customerEmail = $customer->getEmail();
        $this->credentialsValidator->checkPasswordDifferentFromEmail($customerEmail, $newPassword);
        $this->checkPasswordStrength($newPassword);
        $this->authService->changePassword($userTokens['AccessToken'], $currentPassword, $newPassword);
        $this->destroyCustomerSessions($customer->getId());
        return true;
    }

    /** @inheritDoc */
    protected function getEmailNotification()
    {
        if (!($this->emailNotification instanceof EmailNotificationInterface)) {
            return ObjectManager::getInstance()->get(EmailNotificationInterface::class);
        } else {
            return $this->emailNotification;
        }
    }

    /**
     * Overridden to change the max character count. Cognito only allows up to 99
     * @inheritDoc
     */
    protected function checkPasswordStrength($password)
    {
        $length = $this->stringHelper->strlen($password);
        if ($length > 99) {
            throw new InputException(__('Please enter a password with at most %1 characters.', 99));
        }
        $configMinPasswordLength = $this->getMinPasswordLength();
        if ($length < $configMinPasswordLength) {
            throw new InputException(__('The password needs at least %1 characters. Create a new password and try again.', $configMinPasswordLength));
        }
        if ($this->stringHelper->strlen(trim($password)) != $length) {
            throw new InputException(__("The password can't begin or end with a space. Verify the password and try again."));
        }
        $requiredCharactersCheck = $this->makeRequiredCharactersCheck($password);
        if ($requiredCharactersCheck !== 0) {
            throw new InputException(__('Minimum of different classes of characters in password is %1. Classes of characters: Lower Case, Upper Case, Digits, Special Characters.', $requiredCharactersCheck));
        }
    }

    /**
     * Changed the symbol check regex to follow cognito password requirements
     * https://docs.aws.amazon.com/cognito/latest/developerguide/user-pool-settings-policies.html
     * @inheritDoc
     */
    protected function makeRequiredCharactersCheck($password)
    {
        $counter = 0;
        $requiredNumber = $this->scopeConfig->getValue(self::XML_PATH_REQUIRED_CHARACTER_CLASSES_NUMBER);
        $return = 0;
        if (preg_match('/[0-9]+/', $password)) {
            $counter++;
        }
        if (preg_match('/[A-Z]+/', $password)) {
            $counter++;
        }
        if (preg_match('/[a-z]+/', $password)) {
            $counter++;
        }
        $pattern = '/['.preg_quote('^$*.[]{}()?-"!@#%&/\,><\':;|_~`', '/').']+/';
        if (preg_match($pattern, $password)) {
            $counter++;
        }
        if ($counter < $requiredNumber) {
            $return = $requiredNumber;
        }
        return $return;
    }

    /**
     * NO CHANGES
     * Overridden due to private access
     * @inheritDoc
     */
    private function destroyCustomerSessions($customerId)
    {
        $sessionLifetime = $this->scopeConfig->getValue(
            \Magento\Framework\Session\Config::XML_PATH_COOKIE_LIFETIME,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $dateTime = $this->dateTimeFactory->create();
        $activeSessionsTime = $dateTime->setTimestamp($dateTime->getTimestamp() - $sessionLifetime)
            ->format(DateTime::DATETIME_PHP_FORMAT);
        /** @var \Magento\Customer\Model\ResourceModel\Visitor\Collection $visitorCollection */
        $visitorCollection = $this->visitorCollectionFactory->create();
        $visitorCollection->addFieldToFilter('customer_id', $customerId);
        $visitorCollection->addFieldToFilter('last_visit_at', ['from' => $activeSessionsTime]);
        $visitorCollection->addFieldToFilter('session_id', ['neq' => $this->sessionManager->getSessionId()]);
        /** @var \Magento\Customer\Model\Visitor $visitor */
        foreach ($visitorCollection->getItems() as $visitor) {
            $sessionId = $visitor->getSessionId();
            $this->saveHandler->destroy($sessionId);
        }
    }

    /**
     * The count for character groups is a random_int, I decided on the range by looking at the cognito password requirements, 6-99 characters, and divided that among the character groups.
     * So if by chance random_int chooses the lowest for each group, or the highest for each group, it wont be under or over the limit.
     * The list of characters for the symbol group was pulled from the cognito password requirements.
     * Cognito pasword requirements can be found here:
     * https://docs.aws.amazon.com/cognito/latest/developerguide/user-pool-settings-policies.html
     * @throws \Exception
     */
    protected function generateTempPassword()
    {
        $characterGroups = [
            'lowerAlpha' => [
                'count' => random_int(2, 24),
                'characters' => 'abcdefghijklmnopqrstuvwxyz',
            ],
            'upperAlpha' => [
                'count' => random_int(2, 24),
                'characters' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            ],
            'numeric' => [
                'count' => random_int(2, 24),
                'characters' => '0123456789'
            ],
            'symbols' => [
                'count' => random_int(2, 24),
                'characters' => '^$*.[]{}()?-"!@#%&/\\,><\':;|_~`'
            ]
        ];
        $password = '';
        foreach ($characterGroups as $group) {
            for ($i = 0; $i < $group['count']; $i++) {
                $char = substr($group['characters'], random_int(0, strlen($group['characters'])-1), 1);
                if (strlen($password) > 5) {
                    $password = substr_replace($password, $char, random_int(0, strlen($password)-1), 0);
                } else {
                    $password .= $char;
                }
            }
        }
        return $password;
    }

}