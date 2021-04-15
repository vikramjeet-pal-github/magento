<?php
namespace Vonnda\Cognito\Model;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Integration\Model\CredentialsValidator;
use Magento\Integration\Model\Oauth\TokenFactory as TokenModelFactory;
use Magento\Integration\Model\ResourceModel\Oauth\Token\CollectionFactory as TokenCollectionFactory;
use Magento\Integration\Model\Oauth\Token\RequestThrottler;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;

/**
 * Based on the core CustomerTokenService class. Duplicated/modified to provide a magento API token using a cognito token for validation.
 * @see \Magento\Integration\Model\CustomerTokenService
 * @package Vonnda\Cognito\Model
 */
class CustomerTokenService implements \Vonnda\Cognito\Api\CustomerTokenServiceInterface
{

    /** @var TokenModelFactory */
    private $tokenModelFactory;

    /** @var AccountManagementInterface */
    private $accountManagement;

    /** @var CredentialsValidator */
    private $validatorHelper;

    /** @var TokenCollectionFactory */
    private $tokenModelCollectionFactory;

    /** @var RequestThrottler */
    private $requestThrottler;

    /** @var AuthService */
    private $authService;

    /**
     * @param TokenModelFactory $tokenModelFactory
     * @param AccountManagementInterface $accountManagement
     * @param TokenCollectionFactory $tokenModelCollectionFactory
     * @param CredentialsValidator $validatorHelper
     * @param AuthService $authService
     */
    public function __construct(
        TokenModelFactory $tokenModelFactory,
        AccountManagementInterface $accountManagement,
        TokenCollectionFactory $tokenModelCollectionFactory,
        CredentialsValidator $validatorHelper,
        AuthService $authService
    ) {
        $this->tokenModelFactory = $tokenModelFactory;
        $this->accountManagement = $accountManagement;
        $this->tokenModelCollectionFactory = $tokenModelCollectionFactory;
        $this->validatorHelper = $validatorHelper;
        $this->authService = $authService;
    }

    /** {@inheritdoc} */
    public function createCustomerAccessToken($cognitoToken)
    {
        $this->getRequestThrottler()->throttle($cognitoToken, RequestThrottler::USER_TYPE_CUSTOMER);
        try {
            $username = $this->authService->tokenAuth($cognitoToken);
        } catch (\Exception $e) {
            $this->getRequestThrottler()->logAuthenticationFailure($cognitoToken, RequestThrottler::USER_TYPE_CUSTOMER);
            throw new AuthenticationException(__($e->getMessage()));
        }
        try {
            $customer = $this->accountManagement->getCustomer($username, $cognitoToken);
        } catch (\Exception $e) {
            $this->getRequestThrottler()->logAuthenticationFailure($cognitoToken, RequestThrottler::USER_TYPE_CUSTOMER);
            throw new AuthenticationException(__('Unable to authenticate user token.'));
        }
        $this->getRequestThrottler()->resetAuthenticationFailuresCount($cognitoToken, RequestThrottler::USER_TYPE_CUSTOMER);
        return $this->tokenModelFactory->create()->createCustomerToken($customer->getId())->getToken();
    }

    /** {@inheritdoc} */
    public function refreshCognitoAccessToken($idToken, $refreshToken)
    {
        try {
            return $this->authService->refreshAuthentication($idToken, $refreshToken);
        } catch (\Exception $e) {
            throw new AuthenticationException(__('Unable to refresh tokens.'));
        }
    }

    /** {@inheritdoc} */
    public function revokeCustomerAccessToken($customerId)
    {
        $tokenCollection = $this->tokenModelCollectionFactory->create()->addFilterByCustomerId($customerId);
        if ($tokenCollection->getSize() == 0) {
            throw new LocalizedException(__('This customer has no tokens.'));
        }
        try {
            foreach ($tokenCollection as $token) {
                $token->delete();
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__("The tokens couldn't be revoked."));
        }
        return true;
    }

    /**
     * Get request throttler instance
     * @return RequestThrottler
     * @deprecated 100.0.4
     */
    private function getRequestThrottler()
    {
        if (!$this->requestThrottler instanceof RequestThrottler) {
            return \Magento\Framework\App\ObjectManager::getInstance()->get(RequestThrottler::class);
        }
        return $this->requestThrottler;
    }

}