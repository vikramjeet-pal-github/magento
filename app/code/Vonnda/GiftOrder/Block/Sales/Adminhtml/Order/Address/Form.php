<?php
/**
 * @copyright: Copyright © 2020 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */
namespace Vonnda\GiftOrder\Block\Sales\Adminhtml\Order\Address;

use Magento\Sales\Block\Adminhtml\Order\Address\Form as CoreAddress;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Adminhtml sales order address block
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Form extends CoreAddress
{
    /**
     * Address form template
     *
     * @var string
     */
    protected $_template = 'Magento_Sales::order/address/form.phtml';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Directory\Model\ResourceModel\Country\Collection
     */
    private $countriesCollection;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    private $backendQuoteSession;

    /**
     * Order Repository
     *
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param \Magento\Sales\Model\AdminOrder\Create $orderCreate
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Customer\Model\Metadata\FormFactory $customerFormFactory
     * @param \Magento\Customer\Model\Options $options
     * @param \Magento\Customer\Helper\Address $addressHelper
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressService
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Customer\Model\Address\Mapper $addressMapper
     * @param \Magento\Framework\Registry $registry
     * @param OrderRepositoryInterface $orderRepository
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Sales\Model\AdminOrder\Create $orderCreate,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Customer\Model\Metadata\FormFactory $customerFormFactory,
        \Magento\Customer\Model\Options $options,
        \Magento\Customer\Helper\Address $addressHelper,
        \Magento\Customer\Api\AddressRepositoryInterface $addressService,
        \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Magento\Framework\Registry $registry,
        OrderRepositoryInterface $orderRepository,
        array $data = []
    ) {

        $this->orderRepository = $orderRepository;

        parent::__construct(
            $context,
            $sessionQuote,
            $orderCreate,
            $priceCurrency,
            $formFactory,
            $dataObjectProcessor,
            $directoryHelper,
            $jsonEncoder,
            $customerFormFactory,
            $options,
            $addressHelper,
            $addressService,
            $criteriaBuilder,
            $filterBuilder,
            $addressMapper,
            $registry,
            $data
        );
    }

    /**
     * Prepare Form and add elements to form
     *
     * @return $this
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _prepareForm()
    {
        $storeId = $this->getCreateOrderModel()
            ->getSession()
            ->getStoreId();
        $this->_storeManager->setCurrentStore($storeId);

        $fieldset = $this->_form->addFieldset('main', ['no_container' => true]);

        $addressForm = $this->_customerFormFactory->create('customer_address', 'adminhtml_customer_address');
        $attributes = $addressForm->getAttributes();
        $this->_addAttributesToForm($attributes, $fieldset);

        $prefixElement = $this->_form->getElement('prefix');
        if ($prefixElement) {
            $prefixOptions = $this->options->getNamePrefixOptions($this->getStore());
            if (!empty($prefixOptions)) {
                $fieldset->removeField($prefixElement->getId());
                $prefixField = $fieldset->addField($prefixElement->getId(), 'select', $prefixElement->getData(), '^');
                $prefixField->setValues($prefixOptions);
                if ($this->getAddressId()) {
                    $prefixField->addElementValues($this->getAddress()->getPrefix());
                }
            }
        }

        $suffixElement = $this->_form->getElement('suffix');
        if ($suffixElement) {
            $suffixOptions = $this->options->getNameSuffixOptions($this->getStore());
            if (!empty($suffixOptions)) {
                $fieldset->removeField($suffixElement->getId());
                $suffixField = $fieldset->addField(
                    $suffixElement->getId(),
                    'select',
                    $suffixElement->getData(),
                    $this->_form->getElement('lastname')->getId()
                );
                $suffixField->setValues($suffixOptions);
                if ($this->getAddressId()) {
                    $suffixField->addElementValues($this->getAddress()->getSuffix());
                }
            }
        }

        $regionElement = $this->_form->getElement('region_id');
        if ($regionElement) {
            $regionElement->setNoDisplay(true);
        }

        if($this->isGiftOrderShippingAddress()){
            $fieldset->addField(
                'gift_recipient_email',
                'text',
                [
                    'name'  => 'gift_recipient_email',
                    'label' => __('Recipient\'s email'),
                    'title' => __('Gift Recipient Email'),
                ]
            );
        }
        
        $this->_form->setValues($this->getFormValues());

        $countryElement = $this->_form->getElement('country_id');

        $this->processCountryOptions($countryElement);

        if ($countryElement->getValue()) {
            $countryId = $countryElement->getValue();
            $countryElement->setValue(null);
            foreach ($countryElement->getValues() as $country) {
                if ($country['value'] == $countryId) {
                    $countryElement->setValue($countryId);
                }
            }
        }
        if ($countryElement->getValue() === null) {
            $countryElement->setValue(
                $this->directoryHelper->getDefaultCountry($this->getStore())
            );
        }
        // Set custom renderer for VAT field if needed
        $vatIdElement = $this->_form->getElement('vat_id');
        if ($vatIdElement && $this->getDisplayVatValidationButton() !== false) {
            $vatIdElement->setRenderer(
                $this->getLayout()->createBlock(
                    \Magento\Customer\Block\Adminhtml\Sales\Order\Address\Form\Renderer\Vat::class
                )->setJsVariablePrefix(
                    $this->getJsVariablePrefix()
                )
            );
        }

        $this->_form->setId('edit_form');
        $this->_form->setMethod('post');
        $this->_form->setAction(
            $this->getUrl('sales/*/addressSave', ['address_id' => $this->_getAddress()->getId()])
        );
        $this->_form->setUseContainer(true);
    }

    /**
     * Process country options.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $countryElement
     * @return void
     */
    private function processCountryOptions(\Magento\Framework\Data\Form\Element\AbstractElement $countryElement)
    {
        $storeId = $this->getAddressStoreId();
        $options = $this->getCountriesCollection()
            ->loadByStore($storeId)
            ->toOptionArray();

        $countryElement->setValues($options);
    }

    /**
     * Retrieve Directory Countries collection
     *
     * @deprecated 100.1.3
     * @return \Magento\Directory\Model\ResourceModel\Country\Collection
     */
    private function getCountriesCollection()
    {
        if (!$this->countriesCollection) {
            $this->countriesCollection = ObjectManager::getInstance()
                ->get(\Magento\Directory\Model\ResourceModel\Country\Collection::class);
        }

        return $this->countriesCollection;
    }

    /**
     * Retrieve Backend Quote Session
     *
     * @deprecated 100.1.3
     * @return Quote
     */
    private function getBackendQuoteSession()
    {
        if (!$this->backendQuoteSession) {
            $this->backendQuoteSession = ObjectManager::getInstance()->get(Quote::class);
        }

        return $this->backendQuoteSession;
    }

    /**
     * Form header text getter
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        if($this->isGiftOrderShippingAddress()){
            return __('Gift Recipient Address Information');
        }
        
        return __('Order Address Information');
    }

    protected function isGiftOrderShippingAddress()
    {
        $address = $this->_getAddress();
        $order = $this->orderRepository->get($address->getParentId());

        if($order->getGiftOrder() && $address->getAddressType()){
            return true;
        }

        return false;
    }
    
}
