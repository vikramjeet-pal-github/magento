<?php
/**
 * PaymentInformationManagementPlugin.php
 */
declare(strict_types=1);

namespace Vonnda\ShippingRestrictions\Plugin\Checkout;

use Magento\Checkout\{
    Api\PaymentInformationManagementInterface,
    Model\Session
};
use Magento\Framework\{
    App\Config\ScopeConfigInterface,
    Exception\CouldNotSaveException,
    Exception\LocalizedException,
    Exception\NoSuchEntityException,
    Message\ManagerInterface as MessageManagerInterface
};
use Magento\Quote\{
    Api\CartRepositoryInterface,
    Api\Data\AddressInterface,
    Api\Data\PaymentInterface
};
use Magento\Store\{
    Model\StoreManagerInterface,
    Model\Store
};
use Vonnda\ShippingRestrictions\{
    Exception\ExceptionFactory,
    Exception\ShippingRestrictionException,
    Model\Shipping\Restrictions as ShippingRestrictions
};

class PaymentInformationManagementPlugin
{
    /** @property ExceptionFactory $exceptionFactory */
    protected $exceptionFactory;

    /** @property MessageManagerInterface $messageManager */
    protected $messageManager;

    /** @property CartRepositoryInterface $quoteRepository */
    protected $quoteRepository;

    /** @property ShippingRestrictions $restrictions */
    protected $restrictions;

    /** @property Session $session */
    protected $session;

    /** @property StoreManagerInterface $storeManager */
    protected $storeManager;

    /**
     * @param ExceptionFactory $exceptionFactory
     * @param MessageManagerInterface $messageManager
     * @param CartRepositoryInterface $quoteRepository
     * @param ShippingRestrictions $restrictions
     * @param Session $session
     * @param StoreManagerInterface $storeManager
     * @return void
     */
    public function __construct(
        ExceptionFactory $exceptionFactory,
        MessageManagerInterface $messageManager,
        CartRepositoryInterface $quoteRepository,
        ShippingRestrictions $restrictions,
        Session $session,
        StoreManagerInterface $storeManager
    ) {
        $this->exceptionFactory = $exceptionFactory;
        $this->messageManager = $messageManager;
        $this->quoteRepository = $quoteRepository;
        $this->restrictions = $restrictions;
        $this->session = $session;
        $this->storeManager = $storeManager;
    }

    /**
     * @param PaymentInformationManagementInterface $subject
     * @param \Closure $proceed
     * @param int $cartId
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return mixed
     */
    public function aroundSavePaymentInformationAndPlaceOrder(
        PaymentInformationManagementInterface $subject,
        $proceed,
        $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        /** @var array $countries */
        // $allowedCountries = $this->restrictions->getAllowedShippingCountries();
        // /** @var Magento\Quote\Model\Quote $quote */
        // $quote = $this->quoteRepository->get($cartId);

        // /** @var Magento\Quote\Model\Quote\Address $shippingAddress */
        // $shippingAddress = $quote->getShippingAddress();

        // if (!in_array($shippingAddress->getCountryId(), $countries)) {
        //     /** @var ShippingRestrictionException $exception */
        //     $exception = $this->exceptionFactory->create(
        //         ShippingRestrictionException::class,
        //         __('Your shipping address country is restricted.')
        //     );

        //     throw $exception;
        // }

        return $proceed(
            $cartId,
            $paymentMethod,
            $billingAddress
        );

    }
}
