<?php
namespace Vonnda\Checkout\Plugin\Model;

use Aheadworks\OneStepCheckout\Model\Newsletter\PaymentDataExtensionProcessor;
use Vonnda\Checkout\Model\GuestPaymentInformationManagement;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\AddressInterface;

class NewsletterSubscriber
{

    /** @var PaymentDataExtensionProcessor */
    protected $paymentDataProcessor;

    /**
     * @param PaymentDataExtensionProcessor $paymentDataProcessor
     */
    public function __construct(PaymentDataExtensionProcessor $paymentDataProcessor)
    {
        $this->paymentDataProcessor = $paymentDataProcessor;
    }

    public function aroundSavePaymentInformation(
        GuestPaymentInformationManagement $subject,
        \Closure $proceed,
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress,
        $password
    ) {
        $proceed($cartId, $email, $paymentMethod, $billingAddress, $password);
        $this->paymentDataProcessor->process($paymentMethod);
    }

}