<?php
namespace Vonnda\Checkout\Model\Quote\Webapi;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Webapi\Rest\Request\ParamOverriderInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Framework\UrlInterface;

/**
 * Replaces a "%cart_id%" value with the current authenticated customer's cart
 */
class ParamOverriderCartId implements ParamOverriderInterface
{
    /** @var UserContextInterface */
    private $userContext;

    /** @var CartManagementInterface */
    private $cartManagement;

    /** @var UrlInterface */
    protected $urlBuilder;

    /**
     * Constructs an object to override the cart ID parameter on a request.
     * @param UserContextInterface $userContext
     * @param CartManagementInterface $cartManagement
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        UserContextInterface $userContext,
        CartManagementInterface $cartManagement,
        UrlInterface $urlBuilder
    ) {
        $this->userContext = $userContext;
        $this->cartManagement = $cartManagement;
        $this->urlBuilder = $urlBuilder;
    }

    /** {@inheritDoc} */
    public function getOverriddenValue()
    {
        try {
            if ($this->userContext->getUserType() === UserContextInterface::USER_TYPE_CUSTOMER) {
                $customerId = $this->userContext->getUserId();
                /** @var \Magento\Quote\Api\Data\CartInterface */
                $cart = $this->cartManagement->getCartForCustomer($customerId);
                if ($cart) {
                    return $cart->getId();
                }
            }
        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException(new \Magento\Framework\Phrase('Please <a style="text-decoration:underline;" href="'.$this->urlBuilder->getUrl('customer/account/logout').'">sign out</a> and begin shopping again. Apologies for the inconvenience.'));
        }
        return null;
    }

}