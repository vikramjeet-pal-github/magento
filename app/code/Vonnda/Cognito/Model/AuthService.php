<?php
namespace Vonnda\Cognito\Model;

require(__DIR__ .'/../../../../../vendor/autoload.php');

use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Aws\Credentials\Credentials;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Vonnda\Cognito\Helper\JWT;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Framework\Exception\State\UserLockedException;

/**
 * AWS PHP SDK Documentation for the CognitoIdentityProviderClient class
 * https://docs.aws.amazon.com/aws-sdk-php/v3/api/class-Aws.CognitoIdentityProvider.CognitoIdentityProviderClient.html
 */
class AuthService
{

    /** @var ScopeConfigInterface */
    protected $scopeConfig;
    /** @var LoggerInterface */
    protected $logger;
    /** @var StoreManagerInterface */
    protected $storeManager;
    /** @var CognitoIdentityProviderClient */
    protected $client;
    /** @var string */
    protected $appClientId;
    /** @var string */
    protected $appClientSecret;
    /** @var string */
    protected $region;
    /** @var string */
    protected $userPoolId;
    /** @var object */
    protected $jsonWebKey;

    public function __construct(ScopeConfigInterface $scopeConfig, LoggerInterface $logger, StoreManagerInterface $storeManager)
    {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $storeId = $storeManager->getStore()->getId();
        $this->appClientId = $scopeConfig->getValue('customer_cognito/general/app_client_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        $this->appClientSecret = $scopeConfig->getValue('customer_cognito/general/app_client_secret', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        $this->region = $scopeConfig->getValue('customer_cognito/general/user_pool_region', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        $this->userPoolId = $scopeConfig->getValue('customer_cognito/general/user_pool_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        $this->jsonWebKey = json_decode(base64_decode($scopeConfig->getValue('customer_cognito/general/json_web_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId)));
    }

    /**
     * @return CognitoIdentityProviderClient
     * @throws NoSuchEntityException
     */
    public function getClient()
    {
        if (!$this->client) {
            $storeId = $this->storeManager->getStore()->getId();
            $this->client = CognitoIdentityProviderClient::factory([
                'region' => $this->region,
                'version' => 'latest',
                'credentials' => new Credentials(
                    $this->scopeConfig->getValue('customer_cognito/developer/access_key_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId),
                    $this->scopeConfig->getValue('customer_cognito/developer/access_key_secret', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId)
                )
            ]);
        }
        return $this->client;
    }

    /**
     * @param string $username
     * @param string $password
     * @return array
     * @throws EmailNotConfirmedException|NoSuchEntityException|\Exception
     */
    public function authenticate($username, $password)
    {
        try {
			\Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)->debug('authenticate1');
			\Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)->debug(var_export([
                'AuthFlow' => 'USER_PASSWORD_AUTH',
                'AuthParameters' => [
                    'USERNAME' => $username,
                    'PASSWORD' => $password,
                    'SECRET_HASH' => $this->cognitoSecretHash($username)
                ],
                'ClientId' => $this->appClientId,
                'UserPoolId' => $this->userPoolId
            ], true));
            $response = $this->getClient()->InitiateAuth([
                'AuthFlow' => 'USER_PASSWORD_AUTH',
                'AuthParameters' => [
                    'USERNAME' => $username,
                    'PASSWORD' => $password,
                    'SECRET_HASH' => $this->cognitoSecretHash($username)
                ],
                'ClientId' => $this->appClientId,
                'UserPoolId' => $this->userPoolId
            ]);
			\Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)->debug(var_export($response->toArray(), true));
            return $this->handleAuthenticateResponse($response->toArray());
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() == 'UserNotConfirmedException') {
                throw new EmailNotConfirmedException(__('The user has not validated their email address'));
            }
			if ($e->getAwsErrorCode() == 'PasswordResetRequiredException') {
                throw new UserLockedException(__('The user has not validated their email address'));
            }
            $this->logger->critical($e);
            throw new NoSuchEntityException();
        }
    }

    /**
     * @param string $username
     * @param string $password
     * @param array $attributes
     * @return string|bool
     * @throws \Exception
     */
    public function registerUser($username, $password, array $attributes = [])
    {
        try {
			$attributes[] = array('custom:welcome'=>'1');
            $userAttributes = $this->buildAttributesArray($attributes);
            $response = $this->getClient()->signUp([
                'ClientId' => $this->appClientId,
                'Password' => $password,
                'SecretHash' => $this->cognitoSecretHash($username),
                'UserAttributes' => $userAttributes,
                'Username' => $username
            ]);
            return $response->get('UserSub');
        } catch (CognitoIdentityProviderException $e) {
            $this->logger->critical($e->getAwsErrorMessage());
            if ($e->getAwsErrorCode() == 'UsernameExistsException') {
                throw new InputMismatchException(__('A customer with the same email address already exists.'));
            } else {
                throw new \Exception($e->getAwsErrorMessage());
            }
        }
    }

    /**
     * Verifies the access token and returns the email of the cognito user in order to lookup magento user
     * @see https://docs.aws.amazon.com/cognito/latest/developerguide/amazon-cognito-user-pools-using-tokens-verifying-a-jwt.html
     * @param $accessToken
     * @return array
     * @throws \Exception
     */
    public function tokenAuth($accessToken)
    {
        if (JWT::verify($accessToken, $this->jsonWebKey)) { // if the signature is verified, verify the claims in the payload
            $sections = explode('.', $accessToken);
            $payload = json_decode(JWT::urlsafeB64Decode($sections[1]));
            if ($payload->exp < time()) {
                throw new \Exception('The provided token has expired.');
            }
            if ($payload->token_use !== 'access') {
                throw new \Exception('The provided token is of the wrong type.');
            }
            /**
             * TODO: add config setting for allowed client ids and check that here
             * For now, commenting this out since multiple client ids can access a user pool
             *
             * if ($payload->client_id !== $this->appClientId) {
             *     throw new \Exception('The provided token is not for this client.');
             * }
             */
            if ($payload->iss !== "https://cognito-idp.{$this->region}.amazonaws.com/{$this->userPoolId}") {
                throw new \Exception('The provided token is not for this user pool.');
            }
            if ($user = $this->getUser($accessToken)) {
                $attributes = $user->get('UserAttributes');
                foreach ($attributes as $attr) {
                    if ($attr['Name'] == 'email') {
                        return $attr['Value'];
                    }
                }
            }
        }
        throw new \Exception('The access token is invalid');
    }

    /**
     * @param string $challengeName
     * @param array $challengeResponses
     * @param string $session
     * @return array|bool
     * @throws \Exception
     */
    public function respondToAuthChallenge($challengeName, array $challengeResponses, $session)
    {
        try {
            $response = $this->getClient()->respondToAuthChallenge([
                'ChallengeName' => $challengeName,
                'ChallengeResponses' => $challengeResponses,
                'ClientId' => $this->appClientId,
                'Session' => $session
            ]);
            return $this->handleAuthenticateResponse($response->toArray());
        } catch (CognitoIdentityProviderException $e) {
            $this->logger->critical($e);
            return false;
        }
    }

    /**
     * @param string $username
     * @param string $newPassword
     * @param string $session
     * @return array
     * @throws \Exception
     */
    public function respondToNewPasswordRequiredChallenge($username, $newPassword, $session)
    {
        return $this->respondToAuthChallenge(
            'NEW_PASSWORD_REQUIRED',
            [
                'NEW_PASSWORD' => $newPassword,
                'USERNAME' => $username,
                'SECRET_HASH' => $this->cognitoSecretHash($username)
            ],
            $session
        );
    }

    /**
     * The username for the USER_PASSWORD_AUTH authflow is the email, but in this case, it is the uuid.
     * For this call, the @ in the username causes an error.
     * @see https://stackoverflow.com/questions/54430978/unable-to-verify-secret-hash-for-client-at-refresh-token-auth
     * @param string $idToken
     * @param string $refreshToken
     * @return array|bool
     * @throws \Exception
     */
    public function refreshAuthentication($idToken, $refreshToken)
    {
        try {
            $sections = explode('.', $idToken);
            $payload = json_decode(JWT::urlsafeB64Decode($sections[1]));
            $response = $this->getClient()->InitiateAuth([
                'AuthFlow' => 'REFRESH_TOKEN_AUTH',
                'AuthParameters' => [
                    'REFRESH_TOKEN' => $refreshToken,
                    'SECRET_HASH' => $this->cognitoSecretHash($payload->sub)
                ],
                'ClientId' => $this->appClientId,
                'UserPoolId' => $this->userPoolId
            ]);
            return json_encode($this->handleAuthenticateResponse($response->toArray()));
        } catch (CognitoIdentityProviderException $e) {
            $this->logger->critical($e);
            return false;
        }
    }

    /**
     * @param string $confirmationCode
     * @param string $username
     * @throws \Exception
     */
    public function confirmUserRegistration($confirmationCode, $username)
    {
        try {
            $this->getClient()->confirmSignUp([
                'ClientId' => $this->appClientId,
                'ConfirmationCode' => $confirmationCode,
                'SecretHash' => $this->cognitoSecretHash($username),
                'Username' => $username
            ]);
        } catch (CognitoIdentityProviderException $e) {
            $this->logger->critical($e);
        }
    }

    /**
     * @param string $username
     * @return \Aws\Result|bool
     * @throws \Exception
     */
    public function adminGetUser($username)
    {
        try {
            $response = $this->getClient()->adminGetUser([
                'Username' => $username,
                'UserPoolId' => $this->userPoolId
            ]);
            return $response;
        } catch (CognitoIdentityProviderException $e) {
            // do not log UserNotFoundException. adminGetUser is used to check if a user exists in cognito. if not, it throws UserNotFoundException
            if ($e->getAwsErrorCode() != 'UserNotFoundException') {
                $this->logger->critical($e);
            }
            return false;
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return false;
        }
    }

    /**
     * @param string $token
     * @return \Aws\Result|bool
     * @throws \Exception
     */
    public function getUser($token)
    {
        try {
            $response = $this->getClient()->getUser([
                'AccessToken' => $token
            ]);
            return $response;
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return false;
        }
    }

    /**
     * @param string $username
     * @param string $groupName
     * @throws \Exception
     */
    public function addUserToGroup($username, $groupName) {
        try {
            $this->getClient()->adminAddUserToGroup([
                'UserPoolId' => $this->userPoolId,
                'Username' => $username,
                'GroupName' => $groupName
            ]);
        } catch (CognitoIdentityProviderException $e) {
            $this->logger->critical($e);
        }
    }

    /**
     * @param $username
     * @param array $attributes
     * @throws \Exception
     */
    public function updateUserAttributes($username, array $attributes = [])
    {
        $userAttributes = $this->buildAttributesArray($attributes);
        try {
            $this->getClient()->adminUpdateUserAttributes([
                'Username' => $username,
                'UserPoolId' => $this->userPoolId,
                'UserAttributes' => $userAttributes
            ]);
        } catch (CognitoIdentityProviderException $e) {
            $this->logger->critical($e);
            throw new \Exception('There was a problem updating your account');
        }
    }

    /**
     * @param string $accessToken
     * @param string $currentPassword
     * @param string $newPassword
     * @throws \Exception
     */
    public function changePassword($accessToken, $currentPassword, $newPassword)
    {
        try {
            $this->getClient()->changePassword([
                'AccessToken' => $accessToken,
                'PreviousPassword' => $currentPassword,
                'ProposedPassword' => $newPassword
            ]);
        } catch (CognitoIdentityProviderException $e) {
            $this->logger->critical($e);
            throw new \Exception('There was a problem sending the forgot password token');
        }
    }

    /**
     * @param string $username
     * @param string $proposedPassword
     * @throws \Exception
     */
    public function forgotPassword($username)
    {
        try {
            $this->getClient()->forgotPassword([
                'ClientId' => $this->appClientId,
                'SecretHash' => $this->cognitoSecretHash($username),
                'Username' => $username
            ]);
        } catch (CognitoIdentityProviderException $e) {
            $this->logger->critical($e);
            throw new \Exception('There was a problem sending the forgot password token');
        }
    }

    /**
     * @param string $confirmationCode
     * @param string $username
     * @param string $proposedPassword
     * @throws \Exception
     */
    public function confirmForgotPassword($confirmationCode, $username, $proposedPassword)
    {
        try {
            $this->getClient()->confirmForgotPassword([
                'ClientId' => $this->appClientId,
                'ConfirmationCode' => $confirmationCode,
                'Password' => $proposedPassword,
                'SecretHash' => $this->cognitoSecretHash($username),
                'Username' => $username
            ]);
        } catch (CognitoIdentityProviderException $e) {
            $this->logger->critical($e);
            throw new \Exception('There was a problem confirming the password');
        }
    }

    /**
     * @param string $username
     * @throws \Exception
     */
    public function resendRegistrationConfirmationCode($username)
    {
        try {
            $this->getClient()->resendConfirmationCode([
                'ClientId' => $this->appClientId,
                'SecretHash' => $this->cognitoSecretHash($username),
                'Username' => $username
            ]);
        } catch (CognitoIdentityProviderException $e) {
            $this->logger->critical($e);
        }
    }

    /**
     * @param string $username
     * @throws \Exception
     */
    public function sendForgottenPasswordRequest($username)
    {
        try {
            $this->getClient()->forgotPassword([
                'ClientId' => $this->appClientId,
                'SecretHash' => $this->cognitoSecretHash($username),
                'Username' => $username
            ]);
        } catch (CognitoIdentityProviderException $e) {
            $this->logger->critical($e);
        }
    }

    /**
     * @param string $username
     * @return string
     */
    public function cognitoSecretHash($username)
    {
        return $this->hash($username . $this->appClientId);
    }

    /**
     * @param $username
     * @return \Aws\Result|bool
     * @throws \Exception
     */
    public function getGroupsForUsername($username)
    {
        try {
            return $this->getClient()->adminListGroupsForUser([
                'UserPoolId' => $this->userPoolId,
                'Username'   => $username
            ]);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return false;
        }
    }

    /**
     * @param string $message
     * @return string
     */
    protected function hash($message)
    {
        $hash = hash_hmac(
            'sha256',
            $message,
            $this->appClientSecret,
            true
        );
        return base64_encode($hash);
    }

    /**
     * @param array $response
     * @return array
     * @throws \Exception
     */
    protected function handleAuthenticateResponse(array $response)
    {
        if (isset($response['AuthenticationResult'])) {
            return $response['AuthenticationResult'];
        }
        throw new \Exception('Could not handle AdminInitiateAuth response');
    }

    /**
     * @param array $attributes
     * @return array
     */
    private function buildAttributesArray(array $attributes): array
    {
        $userAttributes = [];
        foreach ($attributes as $key => $value) {
            $userAttributes[] = [
                'Name' => (string)$key,
                'Value' => (string)$value
            ];
        }
        return $userAttributes;
    }

}