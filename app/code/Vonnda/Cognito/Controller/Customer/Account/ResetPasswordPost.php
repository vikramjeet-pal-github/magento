<?php
namespace Vonnda\Cognito\Controller\Customer\Account;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\InputException;
use Magento\Customer\Model\Customer\CredentialsValidator;
use Magento\Framework\Encryption\EncryptorInterface as Encryptor;
use Magento\Customer\Model\CustomerFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Store\StoreManager;
use Vonnda\Cognito\Model\AuthService;

class ResetPasswordPost extends \Magento\Customer\Controller\Account\ResetPasswordPost
{

    /** @var AuthService */
    protected $authService;

    /** @var CookieManagerInterface */
    protected $cookieManager;

    /** @var CookieMetdataFactory */
    protected $cookieMetadataFactory;

    /** @var UrlInterface */
    protected $urlInterface;

    /** @var CustomerFactory */
    protected $customerFactory;

    /** @var StoreManager */
    protected $storeManager;

    /** @var Encryptor */
    protected $encryptor;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param AccountManagementInterface $accountManagement
     * @param CustomerRepositoryInterface $customerRepository
     * @param AuthService $authService
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param UrlInterface $urlInterface
     * @param StoreManager $storeManager
     * @param CustomerFactory $customerFactory
     * @param Encryptor
     * @param CredentialsValidator|null $credentialsValidator
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        AccountManagementInterface $accountManagement,
        CustomerRepositoryInterface $customerRepository,
        AuthService $authService,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        UrlInterface $urlInterface,
        CustomerFactory $customerFactory,
        StoreManagerInterface $storeManager,
        Encryptor $encryptor,
        CredentialsValidator $credentialsValidator = null
    ) {
        parent::__construct($context, $customerSession, $accountManagement, $customerRepository, $credentialsValidator);
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->authService = $authService;
        $this->urlInterface  = $urlInterface;
        $this->customerFactory = $customerFactory;
        $this->storeManager = $storeManager;
        $this->encryptor = $encryptor;
    }

    /** @inheritDoc */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resetPasswordToken = (string)$this->getRequest()->getQuery('token');
        $resetPasswordEmail = (string)$this->getRequest()->getQuery('email');		
        $password = (string)$this->getRequest()->getPost('password');
        $passwordConfirmation = (string)$this->getRequest()->getPost('password_confirmation');
		$resetPasswordEmail = strtolower($resetPasswordEmail);
		//Japanese form
		$resetLang = (string)$this->getRequest()->getQuery('lang');
        $passwordConfirmation = (string)$this->getRequest()->getPost('password_confirmation');

		if($resetLang == 'jp'){
			$success = 0;
			try {
				$this->authService->confirmForgotPassword($resetPasswordToken, $resetPasswordEmail, $password);
				//$this->messageManager->addSuccessMessage(__('パスワードが更新されました。'));
				$success = 1;
			} catch (\Exception $exception) {
				$this->messageManager->addErrorMessage(__('問題が発生しました。もう一度やってみてください。'));
			}
            $resultRedirect->setPath('*/*/createPassword', ['_query' =>['token' => $resetPasswordToken,'email' => $resetPasswordEmail,'lang' => 'jp','success' => $success]]);
			return $resultRedirect;
		}
		
		if($resetLang == 'kr'){
			$success = 0;
			try {
				$this->authService->confirmForgotPassword($resetPasswordToken, $resetPasswordEmail, $password);
				$success = 1;
			} catch (\Exception $exception) {
				$this->messageManager->addErrorMessage(__('문제가 발생했습니다. 다시 시도해주세요.'));
			}
            $resultRedirect->setPath('*/*/createPassword', ['_query' =>['token' => $resetPasswordToken,'email' => $resetPasswordEmail,'lang' => 'kr','success' => $success]]);
			return $resultRedirect;
		}
		//Japanese form

        $customer = $this->getCustomer($resetPasswordEmail, $resetPasswordToken);
        if(!$customer){
			$resultRedirect->setPath('*/*/createPassword', ['token' => $resetPasswordToken]);
            return $resultRedirect;
		}
		$resetPasswordEmail = $customer->getEmail();
        
		if ($password !== $passwordConfirmation) {
            $this->messageManager->addErrorMessage(__("New Password and Confirm New Password values didn't match."));
            $resultRedirect->setPath('*/*/createPassword', ['token' => $resetPasswordToken]);
            return $resultRedirect;
        }
        if (iconv_strlen($password) <= 0) {
            $this->messageManager->addErrorMessage(__('Please enter a new password.'));
            $resultRedirect->setPath('*/*/createPassword', ['token' => $resetPasswordToken]);
            return $resultRedirect;
        }
        try {
            /** Overridden toswap the core resetPassword with the cognito version. Also adding in the email unset commented
             * out in ForgotPassword
            $this->accountManagement->resetPassword(null, $resetPasswordToken, $password);
             */
            $this->authService->confirmForgotPassword($resetPasswordToken, $resetPasswordEmail, $password);
            $this->session->unsForgottenEmail();
            $this->session->unsRpToken();
            $this->messageManager->addSuccessMessage(__('You updated your password.'));
            
			//reset magento PWD
			$cognitoCustomer = $this->authService->adminGetUser($resetPasswordEmail);
            if (!$cognitoCustomer && !$cognitoCustomer->get('Enabled')) {
                throw new \Exception("User not found.");
            }
            $userAttributes = $this->extractUserAttributes($cognitoCustomer);
            $uuid = $userAttributes['sub'];
            $hash = $this->encryptor->getHash($uuid.'-mlk2019', true);
			$customerNew = $this->customerRepository->getById($customer->getId());
			$this->customerRepository->save($customerNew, $hash);
			//reset magento PWD
			
			$resultRedirect->setPath('*/*/login');
            $this->setPasswordResetCookie();
            return $resultRedirect;
        } catch (InputException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            foreach ($e->getErrors() as $error) {
                $this->messageManager->addErrorMessage($error->getMessage());
            }
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__('Something went wrong while saving the new password.'));
        }
        $resultRedirect->setPath('*/*/createPassword', ['token' => $resetPasswordToken]);
        return $resultRedirect;
    }

    protected function setPasswordResetCookie()
    {
        $baseUrl = $this->urlInterface->getBaseUrl();
        $baseUrl = str_replace("https://", "", $baseUrl);
        $baseUrl = str_replace("/", "", $baseUrl);
        $metadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDomain("." . $baseUrl) //Because of the strange . being prepended
            ->setPath("/");
        $this->cookieManager->setPublicCookie("passwordResetSuccess", "true", $metadata);
    }

    protected function getCustomer($customerEmail, $resetPasswordToken)
    {
        try {
            $customer = $this->customerRepository->get($customerEmail);
        } catch(NoSuchEntityException $e){
            return $this->createCustomer($customerEmail, $resetPasswordToken);
        }

        return $customer;
    }

    protected function createCustomer($customerEmail, $resetPasswordToken)
    {
        try {
            $cognitoCustomer = $this->authService->adminGetUser($customerEmail);
            if (!$cognitoCustomer && !$cognitoCustomer->get('Enabled')) {
                throw new \Exception("User not found.");
            }

            $userAttributes = $this->extractUserAttributes($cognitoCustomer);
            $uuid = $userAttributes['sub'];
            $hash = $this->encryptor->getHash($uuid.'-mlk2019', true);
            $store = $this->storeManager->getStore();
            $websiteId = $this->storeManager->getStore()->getWebsiteId();
            
            $customer = $this->customerFactory->create();
            $customer->setWebsiteId($websiteId)
                ->setStore($store)
                ->setFirstname(isset($userAttributes['given_name']) ? $userAttributes['given_name'] : "Test" )
                ->setLastname(isset($userAttributes['family_name']) ? $userAttributes['family_name'] : "Test")
                ->setEmail($customerEmail)
                ->setRpToken($resetPasswordToken)
                ->setRpTokenCreatedAt(date('Y-m-d H:i:s'))
                ->setPassword($hash);
            $customer->save();

            $customer->getResource()->load($customer, $customer->getId());
            $customer->setData('cognito_uuid', $uuid)
                ->setAttributeSetId(CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER);
            $customer->getResource()->save($customer);

            return $customer;
        } catch (\Exception $e) {
			print_r($e->getMessage());
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }
    }

    protected function extractUserAttributes($cognitoCustomer)
    {
        foreach($cognitoCustomer->get('UserAttributes') as $attribute){
            $attributeMap[$attribute['Name']] = $attribute['Value'];
        }

        return $attributeMap;
    }

}