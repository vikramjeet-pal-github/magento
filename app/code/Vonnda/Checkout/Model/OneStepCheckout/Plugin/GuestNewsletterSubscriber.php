<?php
/**
 * Copyright 2019 aheadWorks. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vonnda\Checkout\Model\OneStepCheckout\Plugin;

use Aheadworks\OneStepCheckout\Model\Newsletter\PaymentDataExtensionProcessor;
use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\AddressInterface;

/**
 * Class GuestNewsletterSubscriber
 * @package Aheadworks\OneStepCheckout\Model\Plugin
 */
class GuestNewsletterSubscriber
{
    /**
     * @var PaymentDataExtensionProcessor
     */
    private $paymentDataProcessor;

    /**
     * @param PaymentDataExtensionProcessor $paymentDataProcessor
     */
    public function __construct(PaymentDataExtensionProcessor $paymentDataProcessor)
    {
        $this->paymentDataProcessor = $paymentDataProcessor;
    }

    /**
     * @param GuestPaymentInformationManagementInterface $subject
     * @param \Closure $proceed
     * @param string $cartId
     * @param string $email
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @param string $password
     * @return int Order ID
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundSavePaymentInformationAndPlaceOrder(
        GuestPaymentInformationManagementInterface $subject,
        \Closure $proceed,
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null,
        $password = null
    ) {
        $orderId = $proceed($cartId, $email, $paymentMethod, $billingAddress, $password);
        $this->paymentDataProcessor->process($paymentMethod);

        return $orderId;
    }

    /**
     * @param GuestPaymentInformationManagementInterface $subject
     * @param \Closure $proceed
     * @param string $cartId
     * @param string $email
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @param string $password
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundSavePaymentInformation(
        GuestPaymentInformationManagementInterface $subject,
        \Closure $proceed,
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null,
        $password = null
    ) {
        $proceed($cartId, $email, $paymentMethod, $billingAddress, $password);
        $this->paymentDataProcessor->process($paymentMethod);
    }
}
