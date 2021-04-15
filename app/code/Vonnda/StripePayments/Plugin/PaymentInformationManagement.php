<?php

namespace Vonnda\StripePayments\Plugin;

use Magento\Framework\Exception\CouldNotSaveException;
use Vonnda\StripePayments\Logger\Logger;

class PaymentInformationManagement
{
    /**
     * @var \Magento\Checkout\Helper\Data
     */
    private $checkoutHelper;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var \Vonnda\StripePayments\Logger\Logger
     */
    protected $logger;
    
    /**
     * @param \Magento\Checkout\Helper\Data $checkoutHelper
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagement
     * @param \Vonnda\StripePayments\Logger\Logger $logger
     */
    public function __construct(
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \StripeIntegration\Payments\Helper\Rollback $rollback,
        \StripeIntegration\Payments\Helper\Generic $helper,
        Logger $logger
    ) {

        $this->checkoutHelper = $checkoutHelper;
        $this->cartManagement = $cartManagement;
        $this->rollback = $rollback;
        $this->helper = $helper;
        $this->logger = $logger;
    }

    /**
     * Set payment information and place order for a specified cart.
     *
     * Override this method to get correct exceptions instead
     * "An error occurred on the server. Please try to place the order again."
     *
     * @param \Magento\Checkout\Model\PaymentInformationManagement $subject
     * @param \Closure $proceed
     * @param $cartId
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @return int Order ID.
     */
    public function aroundSavePaymentInformationAndPlaceOrder(
        \Magento\Checkout\Model\PaymentInformationManagement $subject,
        \Closure $proceed,
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        try {
            $subject->savePaymentInformation($cartId, $paymentMethod, $billingAddress);
        }
        catch (\Exception $e)
        {
            // Unmasks zip code errors at the checkout
            $message = $e->getMessage();
            $this->logger->critical($message);
            if (strpos($message, 'zip code you supplied failed validation') !== false) {
                throw new CouldNotSaveException(
                    __('<strong>The zip code you supplied failed validation.</strong><br>Please reenter your card details.'),
                    $e
                );
            } else {
                throw $e;
            }
        }
        try
        {
            $orderId = $this->cartManagement->placeOrder($cartId);
            $this->rollback->reset();
        }
        catch (\Exception $e)
        {
            $msg = $e->getMessage();
            $this->logger->critical($msg);
            if (!$this->helper->isAuthenticationRequiredMessage($msg))
            {
                $this->rollback->run();
                $this->checkoutHelper->sendPaymentFailedEmail($this->helper->getQuote(), $msg);
            }

            // Unmasks errors at the checkout, such as card declined messages, authentication needed exceptions etc
            throw new CouldNotSaveException(
                __($e->getMessage()),
                $e
            );
        }

        return $orderId;
    }
}
