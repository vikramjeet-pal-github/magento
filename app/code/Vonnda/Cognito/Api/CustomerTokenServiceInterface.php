<?php
namespace Vonnda\Cognito\Api;

/**
 * Interface providing token generation for Customers
 * @api
 */
interface CustomerTokenServiceInterface
{

    /**
     * Create access token for admin given the customer credentials.
     * @param string $cognitoToken
     * @return string Token created
     * @throws \Magento\Framework\Exception\AuthenticationException
     */
    public function createCustomerAccessToken($cognitoToken);

    /**
     * @param string $idToken
     * @param string $refreshToken
     * @return string Token created
     * @throws \Magento\Framework\Exception\AuthenticationException
     */
    public function refreshCognitoAccessToken($idToken, $refreshToken);

    /**
     * Revoke token by customer id. The function will delete the token from the oauth_token table.
     * @param int $customerId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function revokeCustomerAccessToken($customerId);

}