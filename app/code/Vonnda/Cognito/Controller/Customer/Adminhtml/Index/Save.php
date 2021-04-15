<?php
namespace Vonnda\Cognito\Controller\Customer\Adminhtml\Index;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Customer\Model\Address\Mapper;
use Magento\Customer\Model\AddressRegistry;
use Magento\Customer\Model\EmailNotificationInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\DataObjectFactory as ObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Vonnda\Cognito\Model\AuthService;
use Vonnda\TealiumTags\Helper\EditPost as TealiumHelper;

/** @SuppressWarnings(PHPMD.CouplingBetweenObjects) */
class Save extends \Magento\Customer\Controller\Adminhtml\Index\Save
{

    /** @var TealiumHelper */
    protected $tealiumHelper;

    /** @var AuthService */
    protected $authService;

    /**  @var EmailNotificationInterface */
    private $emailNotification;

    /** @var AddressRegistry */
    private $addressRegistry;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\AddressFactory $addressFactory
     * @param \Magento\Customer\Model\Metadata\FormFactory $formFactory
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Magento\Customer\Helper\View $viewHelper
     * @param \Magento\Framework\Math\Random $random
     * @param CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param Mapper $addressMapper
     * @param AccountManagementInterface $customerAccountManagement
     * @param AddressRepositoryInterface $addressRepository
     * @param CustomerInterfaceFactory $customerDataFactory
     * @param AddressInterfaceFactory $addressDataFactory
     * @param \Magento\Customer\Model\Customer\Mapper $customerMapper
     * @param \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor
     * @param DataObjectHelper $dataObjectHelper
     * @param ObjectFactory $objectFactory
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param TealiumHelper $tealiumHelper
     * @param AuthService $authService
     * @param AddressRegistry $addressRegistry
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Magento\Customer\Model\Metadata\FormFactory $formFactory,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Customer\Helper\View $viewHelper,
        \Magento\Framework\Math\Random $random,
        CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        Mapper $addressMapper,
        AccountManagementInterface $customerAccountManagement,
        AddressRepositoryInterface $addressRepository,
        CustomerInterfaceFactory $customerDataFactory,
        AddressInterfaceFactory $addressDataFactory,
        \Magento\Customer\Model\Customer\Mapper $customerMapper,
        \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor,
        DataObjectHelper $dataObjectHelper,
        ObjectFactory $objectFactory,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        AddressRegistry $addressRegistry,
        TealiumHelper $tealiumHelper,
        AuthService $authService
    ) {
        parent::__construct($context, $coreRegistry, $fileFactory, $customerFactory, $addressFactory, $formFactory, $subscriberFactory, $viewHelper,
            $random, $customerRepository, $extensibleDataObjectConverter, $addressMapper, $customerAccountManagement, $addressRepository,
            $customerDataFactory, $addressDataFactory, $customerMapper, $dataObjectProcessor, $dataObjectHelper, $objectFactory, $layoutFactory,
            $resultLayoutFactory, $resultPageFactory, $resultForwardFactory, $resultJsonFactory, $addressRegistry);
        $this->addressRegistry = $addressRegistry;
        $this->tealiumHelper = $tealiumHelper;
        $this->authService = $authService;
    }

    /** @inheritDoc */
    public function execute()
    {
        $returnToEdit = false;
        $customerId = $this->getCurrentCustomerId();
        if ($this->getRequest()->getPostValue()) {
            try {
                // optional fields might be set in request for future processing by observers in other modules
                $customerData = $this->_extractCustomerData();
                if ($customerId) {
                    $currentCustomer = $this->_customerRepository->getById($customerId);
                    // No need to validate customer address while editing customer profile
                    $this->disableAddressValidation($currentCustomer);
                    $customerData = array_merge($this->customerMapper->toFlatArray($currentCustomer), $customerData);
                    $customerData['id'] = $customerId;
                }
                if (isset($currentCustomer) && $customerData['email'] != $currentCustomer->getEmail()) {
                    /**
                     * Confirming the email is not in use. Must check cognito due to the possibility of a user existing in cognito and not magento.
                     * Tested before this check was implemented, and the exception thrown below is the same as what magento throws if the email already exists in magento.
                     */
                    if ($this->authService->adminGetUser($customerData['email']) !== false) {
                        $errorMessage = 'A customer with the same email address already exists in an associated website.';
                        $this->tealiumHelper->createEditEmailFailureEventByCustomer($errorMessage, $currentCustomer);
                        throw new LocalizedException(__($errorMessage));
                    }
                    $this->authService->updateUserAttributes($currentCustomer->getEmail(), ['email' => $customerData['email'], 'email_verified' => 'true']);
                    $this->tealiumHelper->createEditEmailEventByCustomer($currentCustomer, $customerData['email']);
                }
                /** @var CustomerInterface $customer */
                $customer = $this->customerDataFactory->create();
                $this->dataObjectHelper->populateWithArray($customer, $customerData, CustomerInterface::class);
                $this->_eventManager->dispatch('adminhtml_customer_prepare_save', ['customer' => $customer, 'request' => $this->getRequest()]);
                if (isset($customerData['sendemail_store_id'])) {
                    $customer->setStoreId($customerData['sendemail_store_id']);
                }
                if ($customerId) {
                    $this->_customerRepository->save($customer);
                    try {
                        $this->getEmailNotification()->credentialsChanged($customer, $currentCustomer->getEmail());
                    } catch (LocalizedException $exception) {
                        $this->_addSessionErrorMessages($exception->getMessage());
                    } catch (\Exception $exception) {
                        $this->messageManager->addException($exception, __('Unable to send the email to the customer.'));
                    }
                } else {
                    $customer = $this->customerAccountManagement->createAccount($customer);
                    $customerId = $customer->getId();
                }
                $isSubscribed = null;
                if ($this->_authorization->isAllowed(null)) {
                    $isSubscribed = $this->getRequest()->getPost('subscription');
                }
                if ($isSubscribed !== null) {
                    if ($isSubscribed !== '0') {
                        $this->_subscriberFactory->create()->subscribeCustomerById($customerId);
                    } else {
                        $this->_subscriberFactory->create()->unsubscribeCustomerById($customerId);
                    }
                }
                $this->_eventManager->dispatch('adminhtml_customer_save_after', ['customer' => $customer, 'request' => $this->getRequest()]);
                $this->_getSession()->unsCustomerFormData();
                $this->_coreRegistry->register(RegistryConstants::CURRENT_CUSTOMER_ID, $customerId);
                $this->messageManager->addSuccess(__('You saved the customer.'));
                $returnToEdit = (bool)$this->getRequest()->getParam('back', false);
            } catch (\Magento\Framework\Validator\Exception $exception) {
                $messages = $exception->getMessages();
                if (empty($messages)) {
                    $messages = $exception->getMessage();
                }
                $this->_addSessionErrorMessages($messages);
                $this->_getSession()->setCustomerFormData($this->retrieveFormattedFormData());
                $returnToEdit = true;
            } catch (\Magento\Framework\Exception\AbstractAggregateException $exception) {
                $errors = $exception->getErrors();
                $messages = [];
                foreach ($errors as $error) {
                    $messages[] = $error->getMessage();
                }
                $this->_addSessionErrorMessages($messages);
                $this->_getSession()->setCustomerFormData($this->retrieveFormattedFormData());
                $returnToEdit = true;
            } catch (LocalizedException $exception) {
                $this->_addSessionErrorMessages($exception->getMessage());
                $this->_getSession()->setCustomerFormData($this->retrieveFormattedFormData());
                $returnToEdit = true;
            } catch (\Exception $exception) {
                $this->messageManager->addException($exception, __('Something went wrong while saving the customer.'));
                $this->_getSession()->setCustomerFormData($this->retrieveFormattedFormData());
                $returnToEdit = true;
            }
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($returnToEdit) {
            if ($customerId) {
                $resultRedirect->setPath('customer/*/edit', ['id' => $customerId, '_current' => true]);
            } else {
                $resultRedirect->setPath('customer/*/new', ['_current' => true]);
            }
        } else {
            $resultRedirect->setPath('customer/index');
        }
        return $resultRedirect;
    }



    /** @inheritDoc */
    private function getEmailNotification()
    {
        if (!($this->emailNotification instanceof EmailNotificationInterface)) {
            return \Magento\Framework\App\ObjectManager::getInstance()->get(EmailNotificationInterface::class);
        } else {
            return $this->emailNotification;
        }
    }

    /** @inheritDoc */
    private function getCurrentCustomerId()
    {
        $originalRequestData = $this->getRequest()->getPostValue(CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER);
        $customerId = isset($originalRequestData['entity_id']) ? $originalRequestData['entity_id'] : null;
        return $customerId;
    }

    /** @inheritDoc */
    private function disableAddressValidation($customer)
    {
        foreach ($customer->getAddresses() as $address) {
            $addressModel = $this->addressRegistry->retrieve($address->getId());
            $addressModel->setShouldIgnoreValidation(true);
        }
    }

    /** @inheritDoc */
    private function retrieveFormattedFormData(): array
    {
        $originalRequestData = $this->getRequest()->getPostValue();
        /* Customer data filtration */
        if (isset($originalRequestData['customer'])) {
            $customerData = $this->_extractData('adminhtml_customer', CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER, [], 'customer');
            $customerData = array_intersect_key($customerData, $originalRequestData['customer']);
            $originalRequestData['customer'] = array_merge($originalRequestData['customer'], $customerData);
        }
        return $originalRequestData;
    }

}