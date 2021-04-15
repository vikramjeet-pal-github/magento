<?php

namespace Vonnda\Residential\Plugin\OneStepCheckout;

use Magento\Checkout\Api\GuestShippingInformationManagementInterface;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

use Vonnda\Residential\Model\OneStepCheckout\Request\Payload\Json as JsonPayload;

class GuestShippingInformationManagement
{
    public const FORM = 'addressInformation';

    public const SCOPE = 'shipping_address';

    public const CODE = 'is_residential';

    protected $checkoutSession;

    protected $jsonPayload;

    public function __construct(
        CheckoutSession $checkoutSession,
        JsonPayload $jsonPayload
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->jsonPayload = $jsonPayload;
    }

    public function beforeSaveAddressInformation(
        GuestShippingInformationManagementInterface $subject,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        $attribute = $this->jsonPayload->getCustomAttribute(
            static::SCOPE,
            static::CODE,
            static::FORM
        );

        if ($attribute !== null) {
            $value = (bool) $attribute->getIsResidential();
            $quote = $this->checkoutSession->getQuote();

            $isResidentialInfo = [
                'cart_id' => $quote->getId(),
                'is_residential' => $value
            ];
            $this->checkoutSession->setIsResidentialInfo($isResidentialInfo);
        }

        return [$cartId, $addressInformation];
    }
}
