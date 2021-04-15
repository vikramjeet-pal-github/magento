<?php
namespace Vonnda\Checkout\Plugin\CheckoutAgreements\Model\Checkout\Plugin;

use Magento\Checkout\Api\AgreementsValidatorInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\CheckoutAgreements\Api\CheckoutAgreementsListInterface;
use Magento\CheckoutAgreements\Model\Api\SearchCriteria\ActiveStoreAgreementsFilter;
use Vonnda\Checkout\Helper\Data as CheckoutHelper;

class Validation extends \Magento\CheckoutAgreements\Model\Checkout\Plugin\Validation
{

    /** @var CheckoutHelper */
    protected $checkoutHelper;

    /**
     * @param AgreementsValidatorInterface $agreementsValidator
     * @param ScopeConfigInterface $scopeConfiguration
     * @param CheckoutAgreementsListInterface $checkoutAgreementsList
     * @param ActiveStoreAgreementsFilter $activeStoreAgreementsFilter
     * @param CheckoutHelper $checkoutHelper
     */
    public function __construct(
        AgreementsValidatorInterface $agreementsValidator,
        ScopeConfigInterface $scopeConfiguration,
        CheckoutAgreementsListInterface $checkoutAgreementsList,
        ActiveStoreAgreementsFilter $activeStoreAgreementsFilter,
        CheckoutHelper $checkoutHelper
    ) {
        parent::__construct($agreementsValidator, $scopeConfiguration, $checkoutAgreementsList, $activeStoreAgreementsFilter);
        $this->checkoutHelper = $checkoutHelper;
    }

    /** @inheritDoc */
    public function beforeSavePaymentInformationAndPlaceOrder(
        \Magento\Checkout\Api\PaymentInformationManagementInterface $subject,
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        if ($this->checkoutHelper->isDeviceInCart() && $this->isAgreementEnabled()) {
            $this->validateAgreements($paymentMethod);
        }
    }

    /** @inheritDoc */
    public function beforeSavePaymentInformation(
        \Magento\Checkout\Api\PaymentInformationManagementInterface $subject,
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        if ($this->checkoutHelper->isDeviceInCart() && $this->isAgreementEnabled()) {
            $this->validateAgreements($paymentMethod);
        }
    }

}