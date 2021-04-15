<?php
namespace Vonnda\Checkout\Model\Quote;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Address\ToOrder as ToOrderConverter;
use Magento\Quote\Model\Quote\Address\ToOrderAddress as ToOrderAddressConverter;
use Magento\Quote\Model\Quote\Item\ToOrderItem as ToOrderItemConverter;
use Magento\Quote\Model\Quote\Payment\ToOrderPayment as ToOrderPaymentConverter;
use Magento\Sales\Api\Data\OrderInterfaceFactory as OrderFactory;
use Magento\Sales\Api\OrderManagementInterface as OrderManagement;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Model\SubmitQuoteValidator;
use Magento\Quote\Model\CustomerManagement;


/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class QuoteManagement extends \Magento\Quote\Model\QuoteManagement implements \Magento\Quote\Api\CartManagementInterface
{

    /** @var \Magento\Quote\Model\QuoteIdMaskFactory */
    protected $quoteIdMaskFactory;

    /** @var \Magento\Customer\Api\AddressRepositoryInterface */
    protected $addressRepository;

    /** @var array */
    protected $addressesToSync = [];

    /**
     * @param EventManager $eventManager
     * @param SubmitQuoteValidator $quoteValidator
     * @param OrderFactory $orderFactory
     * @param OrderManagement $orderManagement
     * @param CustomerManagement $customerManagement
     * @param ToOrderConverter $quoteAddressToOrder
     * @param ToOrderAddressConverter $quoteAddressToOrderAddress
     * @param ToOrderItemConverter $quoteItemToOrderItem
     * @param ToOrderPaymentConverter $quotePaymentToOrderPayment
     * @param UserContextInterface $userContext
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Model\CustomerFactory $customerModelFactory
     * @param \Magento\Quote\Model\Quote\AddressFactory $quoteAddressFactory,
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Api\AccountManagementInterface $accountManagement
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Quote\Model\QuoteIdMaskFactory|null $quoteIdMaskFactory
     * @param \Magento\Customer\Api\AddressRepositoryInterface|null $addressRepository
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        EventManager $eventManager,
        SubmitQuoteValidator $quoteValidator,
        OrderFactory $orderFactory,
        OrderManagement $orderManagement,
        CustomerManagement $customerManagement,
        ToOrderConverter $quoteAddressToOrder,
        ToOrderAddressConverter $quoteAddressToOrderAddress,
        ToOrderItemConverter $quoteItemToOrderItem,
        ToOrderPaymentConverter $quotePaymentToOrderPayment,
        UserContextInterface $userContext,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\CustomerFactory $customerModelFactory,
        \Magento\Quote\Model\Quote\AddressFactory $quoteAddressFactory,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\AccountManagementInterface $accountManagement,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory = null,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository = null
    ) {
        parent::__construct(
            $eventManager,
            $quoteValidator,
            $orderFactory,
            $orderManagement,
            $customerManagement,
            $quoteAddressToOrder,
            $quoteAddressToOrderAddress,
            $quoteItemToOrderItem,
            $quotePaymentToOrderPayment,
            $userContext,
            $quoteRepository,
            $customerRepository,
            $customerModelFactory,
            $quoteAddressFactory,
            $dataObjectHelper,
            $storeManager,
            $checkoutSession,
            $customerSession,
            $accountManagement,
            $quoteFactory,
            $quoteIdMaskFactory,
            $addressRepository
        );
        $this->quoteIdMaskFactory = $quoteIdMaskFactory ?: ObjectManager::getInstance()
            ->get(\Magento\Quote\Model\QuoteIdMaskFactory::class);
        $this->addressRepository = $addressRepository ?: ObjectManager::getInstance()
            ->get(\Magento\Customer\Api\AddressRepositoryInterface::class);
    }

    /**
     * Overwrote method to remove check for save_in_address_book so all new customer addresss will be saved by default.
     * @inheritDoc
     */
    protected function _prepareCustomerQuote($quote)
    {
        $residentialInfo = $this->checkoutSession->getIsResidentialInfo();
        $billing = $quote->getBillingAddress();
        $shipping = $quote->isVirtual() ? null : $quote->getShippingAddress();
        $customer = $this->customerRepository->getById($quote->getCustomerId());
        $hasDefaultBilling = (bool)$customer->getDefaultBilling();
        $hasDefaultShipping = (bool)$customer->getDefaultShipping();
        if ($shipping && !$shipping->getSameAsBilling() && !$shipping->getCustomerAddressId()) {
            if ($shipping->getQuoteId()) {
                $shippingAddress = $shipping->exportCustomerAddress();
            } elseif ($hasDefaultShipping) {
                try {
                    $shippingAddress = $this->addressRepository->getById($customer->getDefaultShipping());
                } catch (LocalizedException $e) {
                    // no address
                }
            }
            if (isset($shippingAddress)) {
                if (!$hasDefaultShipping) {
                    //Make provided address as default shipping address
                    $shippingAddress->setIsDefaultShipping(true);
                    $hasDefaultShipping = true;
                }
                //save here new customer address
                if($residentialInfo && ($residentialInfo['cart_id'] == $quote->getId())){
                    $shippingAddress->setCustomAttribute('is_residential', $residentialInfo['is_residential']);
                }
                $shippingAddress->setCustomerId($quote->getCustomerId());
                $this->addressRepository->save($shippingAddress);
                $quote->addCustomerAddress($shippingAddress);
                $shipping->setCustomerAddressData($shippingAddress);
                $this->addressesToSync[] = $shippingAddress->getId();
                $shipping->setCustomerAddressId($shippingAddress->getId());
            }
        }

        if (!$billing->getCustomerAddressId()) {
            if ($billing->getQuoteId()) {
                $billingAddress = $billing->exportCustomerAddress();
            } elseif ($hasDefaultBilling) {
                try {
                    $billingAddress = $this->addressRepository->getById($customer->getDefaultBilling());
                } catch (LocalizedException $e) {
                    // no address
                }
            }
            if (isset($billingAddress)) {
                if (!$hasDefaultBilling) {
                    //Make provided address as default shipping address
                    if (!$hasDefaultShipping) {
                        //Make provided address as default shipping address
                        $billingAddress->setIsDefaultShipping(true);
                    }
                    $billingAddress->setIsDefaultBilling(true);
                }
                if($residentialInfo && ($residentialInfo['cart_id'] == $quote->getId())){
                    $billingAddress->setCustomAttribute('is_residential', $residentialInfo['is_residential']);
                }
                $billingAddress->setCustomerId($quote->getCustomerId());
                $this->addressRepository->save($billingAddress);
                $quote->addCustomerAddress($billingAddress);
                $billing->setCustomerAddressData($billingAddress);
                $this->addressesToSync[] = $billingAddress->getId();
                $billing->setCustomerAddressId($billingAddress->getId());
            }
        }
        if ($shipping && !$shipping->getCustomerId() && !$hasDefaultBilling) {
            $shipping->setIsDefaultBilling(true);
        }
        
    }

}