<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Checkout\Model\OneStepCheckout;

use Aheadworks\OneStepCheckout\Api\Data\CheckoutSectionsDetailsInterface;
use Aheadworks\OneStepCheckout\Api\Data\CheckoutSectionsDetailsInterfaceFactory;
use Aheadworks\OneStepCheckout\Api\Data\CheckoutSectionInformationInterface;
use Aheadworks\OneStepCheckout\Api\CheckoutSectionsManagementInterface;
use Aheadworks\OneStepCheckout\Model\GiftMessage\DataProvider as GiftMessageDataProvider;
use Aheadworks\OneStepCheckout\Model\Config;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Api\Data\ShippingInformationInterfaceFactory;
use Magento\Checkout\Api\ShippingInformationManagementInterface;
use Magento\Framework\Exception\InputException;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Quote\Api\ShipmentEstimationInterface;
use Magento\Quote\Api\ShippingMethodManagementInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentMethodInterface;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Quote\Api\Data\TotalsInterface;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;


/**
 * 
 * Class ported from Aheadworks in entirety due to use of private methods/variables
 *  almost exclusively.
 * 
 * Class CheckoutSectionsManagement
 * @package Aheadworks\OneStepCheckout\Model
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CheckoutSectionsManagement implements CheckoutSectionsManagementInterface
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var PaymentMethodManagementInterface
     */
    private $paymentMethodManagement;

    /**
     * @var CartTotalRepositoryInterface
     */
    private $totalsRepository;

    /**
     * @var ShipmentEstimationInterface
     */
    private $shipmentEstimation;

    /**
     * @var ShippingMethodManagementInterface
     */
    private $shippingMethodManagement;

    /**
     * @var ShippingInformationInterfaceFactory
     */
    private $shippingInformationFactory;

    /**
     * @var ShippingInformationManagementInterface
     */
    private $shippingInformationManagement;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CheckoutSectionsDetailsInterfaceFactory
     */
    private $sectionsDetailsFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var GiftMessageDataProvider
     */
    private $giftMessageDataProvider;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param PaymentMethodManagementInterface $paymentMethodManagement
     * @param CartTotalRepositoryInterface $totalsRepository
     * @param ShipmentEstimationInterface $shipmentEstimation
     * @param ShippingMethodManagementInterface $shippingMethodManagement
     * @param ShippingInformationManagementInterface $shippingInformationManagement
     * @param ShippingInformationInterfaceFactory $shippingInformationFactory
     * @param LoggerInterface $logger
     * @param CheckoutSectionsDetailsInterfaceFactory $sectionsDetailsFactory
     * @param Config $config
     * @param GiftMessageDataProvider $giftMessageDataProvider
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        PaymentMethodManagementInterface $paymentMethodManagement,
        CartTotalRepositoryInterface $totalsRepository,
        ShipmentEstimationInterface $shipmentEstimation,
        ShippingMethodManagementInterface $shippingMethodManagement,
        ShippingInformationManagementInterface $shippingInformationManagement,
        ShippingInformationInterfaceFactory $shippingInformationFactory,
        LoggerInterface $logger,
        CheckoutSectionsDetailsInterfaceFactory $sectionsDetailsFactory,
        Config $config,
        GiftMessageDataProvider $giftMessageDataProvider
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->totalsRepository = $totalsRepository;
        $this->shipmentEstimation = $shipmentEstimation;
        $this->shippingMethodManagement = $shippingMethodManagement;
        $this->shippingInformationManagement = $shippingInformationManagement;
        $this->shippingInformationFactory = $shippingInformationFactory;
        $this->logger = $logger;
        $this->sectionsDetailsFactory = $sectionsDetailsFactory;
        $this->config = $config;
        $this->giftMessageDataProvider = $giftMessageDataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getSectionsDetails(
        $cartId,
        $sections,
        AddressInterface $shippingAddress = null,
        AddressInterface $billingAddress = null,
        $negotiableQuoteId = null
    ) {
        /** @var CheckoutSectionsDetailsInterface $sectionsDetails */
        $sectionsDetails = $this->sectionsDetailsFactory->create();

        if ($negotiableQuoteId !== null) {
            $cartId = $negotiableQuoteId;
        }
        $sectionCodes = $this->getSectionCodes($sections);
        if (!empty($shippingAddress)) {
            if (in_array('shippingMethods', $sectionCodes)) {
                $shippingMethods = $this->getShippingMethods($cartId, $shippingAddress);
                $methodToSet = $this->resolveShippingMethod(
                    $shippingMethods,
                    $this->getShippingMethod($cartId),
                    $this->config->getDefaultShippingMethod()
                );
                if ($methodToSet) {
                    $this->saveShippingInformation(
                        $cartId,
                        $methodToSet,
                        $shippingAddress,
                        $billingAddress
                    );
                }
                $sectionsDetails->setShippingMethods($shippingMethods);
            }
            if (in_array('paymentMethods', $sectionCodes)) {
                $sectionsDetails->setPaymentMethods(
                    $this->getPaymentMethods($cartId, $shippingAddress, $billingAddress)
                );
            }
        }
        if (in_array('totals', $sectionCodes)) {
            $sectionsDetails->setTotals($this->getTotals($cartId));
        }
        if (in_array('giftMessage', $sectionCodes)) {
            $sectionsDetails->setGiftMessage($this->giftMessageDataProvider->getData($cartId));
        }

        return $sectionsDetails;
    }

    /**
     * Get shipping methods - modified to remove Dark Store option
     *
     * @param int $cartId
     * @param AddressInterface $shippingAddress
     * @return ShippingMethodInterface[]
     */
    private function getShippingMethods($cartId, AddressInterface $shippingAddress)
    {
        //Values hard-coded for now
        $mh1DeviceCode = "MH1_Device_US";
        $sameDayCode = "tablerate_same-day-delivery";

        if ($shippingAddress->getCustomerAddressId()) {
            $shippingMethods = $this->shippingMethodManagement->estimateByAddressId(
                $cartId,
                $shippingAddress->getCustomerAddressId()
            );
        } else {
            $shippingMethods = $this->shipmentEstimation->estimateByExtendedAddress($cartId, $shippingAddress);
        }

        $quote = $this->quoteRepository->get($cartId);
        $quoteItems = $quote->getItems();
        $hasNonMH1Device = false;
        foreach($quoteItems as $quoteItem){
            if($quoteItem->getSku() !== $mh1DeviceCode){
                $hasNonMH1Device = true;
            }
        }

        if(!$hasNonMH1Device){
            return $shippingMethods;
        }

        $filteredShippingMethods = [];
        foreach($shippingMethods as $shippingMethod){
            $isSameDay = ($shippingMethod->getCarrierCode() . "_" . $shippingMethod->getMethodCode()) ===
                $sameDayCode;
            if(!$isSameDay){
                $filteredShippingMethods[] = $shippingMethod;
            }
        }
        
        return $filteredShippingMethods;
    }

    /**
     * 
     * PRIVATE FUNCTION - UNTOUCHED
     * 
     * @param int $cartId
     * @param string $method
     * @param AddressInterface $shippingAddress
     * @param AddressInterface|null $billingAddress
     * @return void
     */
    private function saveShippingInformation(
        $cartId,
        $method,
        AddressInterface $shippingAddress,
        AddressInterface $billingAddress = null
    ) {
        $methodComponents = explode('_', $method);
        $carrierCode = array_shift($methodComponents);
        $methodCode = implode('_', $methodComponents);

        /** @var ShippingInformationInterface $shippingInformation */
        $shippingInformation = $this->shippingInformationFactory->create();
        $shippingInformation
            ->setShippingAddress($shippingAddress)
            ->setShippingCarrierCode($carrierCode)
            ->setShippingMethodCode($methodCode);
        if ($billingAddress) {
            $shippingInformation->setBillingAddress($billingAddress);
        }
        $this->shippingInformationManagement->saveAddressInformation($cartId, $shippingInformation);
    }

    /**
     * 
     * PRIVATE FUNCTION - UNTOUCHED
     * 
     * Get selected shipping method for cart
     *
     * @param int $cartId
     * @return string|null
     */
    private function getShippingMethod($cartId)
    {
        /** @var Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        $cartExtension = $quote->getExtensionAttributes();
        if ($cartExtension) {
            /** @var \Magento\Quote\Api\Data\ShippingAssignmentInterface[] $shippingAssignments */
            $shippingAssignments = $cartExtension->getShippingAssignments();
            if ($shippingAssignments) {
                $shippingAssignment = $shippingAssignments[0];
                $shipping = $shippingAssignment->getShipping();
                if ($shipping) {
                    return $shipping->getMethod();
                }
            }
        }
        return null;
    }

    /**
     * 
     * PRIVATE FUNCTION - UNTOUCHED
     * 
     * Check if shipping method presented in the list
     *
     * @param string $method
     * @param ShippingMethodInterface[] $shippingMethods
     * @return bool
     */
    private function hasShippingMethod($method, $shippingMethods)
    {
        foreach ($shippingMethods as $shippingMethod) {
            if ($method == $shippingMethod->getCarrierCode() . '_' . $shippingMethod->getMethodCode()) {
                return true;
            }
        }
        return false;
    }

    /**
     * 
     * PRIVATE FUNCTION - UNTOUCHED
     * 
     * Resolve shipping method to save
     *
     * @param ShippingMethodInterface[] $allMethods
     * @param string $currentMethod
     * @param string $defaultMethod
     * @return string|null
     */
    private function resolveShippingMethod($allMethods, $currentMethod, $defaultMethod)
    {
        $singleMethod = count($allMethods) == 1
            ? $allMethods[0]->getCarrierCode() . '_' . $allMethods[0]->getMethodCode()
            : null;
        if ($currentMethod) {
            if (
                !$this->hasShippingMethod($currentMethod, $allMethods)
                && $defaultMethod
                && $this->hasShippingMethod($defaultMethod, $allMethods)
            ) {
                return $defaultMethod;
            } elseif ($singleMethod && $singleMethod != $currentMethod) {
                return $singleMethod;
            }
        } elseif ($defaultMethod && $this->hasShippingMethod($defaultMethod, $allMethods)) {
            return $defaultMethod;
        }
        if (!$currentMethod && $singleMethod) {
            return $singleMethod;
        }
        return null;
    }

    /**
     * 
     * PRIVATE FUNCTION - UNTOUCHED
     * 
     * Get payment methods
     *
     * @param int $cartId
     * @param AddressInterface $shippingAddress
     * @param AddressInterface|null $billingAddress
     * @return PaymentMethodInterface[]
     * @throws InputException
     */
    private function getPaymentMethods(
        $cartId,
        AddressInterface $shippingAddress,
        AddressInterface $billingAddress = null
    ) {
        if (!$shippingAddress->getCustomerAddressId()) {
            $shippingAddress->setCustomerAddressId(null);
        }

        if (!$shippingAddress->getCountryId()) {
            return [];
        }

        /** @var Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        $quote
            ->setIsMultiShipping(false)
            ->setShippingAddress($shippingAddress);
        if ($billingAddress) {
            if (!$billingAddress->getCustomerAddressId()) {
                $billingAddress->setCustomerAddressId(null);
            }
            $quote->setBillingAddress($billingAddress);
        }

        try {
            $this->quoteRepository->save($quote);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new InputException(__('Unable to retrieve payment methods. Please check input data.'));
        }

        return $this->paymentMethodManagement->getList($cartId);
    }

    /**
     * 
     * PRIVATE FUNCTION - UNTOUCHED
     * 
     * Get totals
     *
     * @param int $cartId
     * @return TotalsInterface
     */
    private function getTotals($cartId)
    {
        return $this->totalsRepository->get($cartId);
    }

    /**
     * 
     * PRIVATE FUNCTION - UNTOUCHED
     * 
     * Get section codes
     *
     * @param CheckoutSectionInformationInterface[] $sections
     * @return array
     */
    private function getSectionCodes($sections)
    {
        $codes = [];
        foreach ($sections as $section) {
            $codes[] = $section->getCode();
        }
        return $codes;
    }
}
