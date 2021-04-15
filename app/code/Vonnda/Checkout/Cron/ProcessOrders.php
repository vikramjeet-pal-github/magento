<?php
namespace Vonnda\Checkout\Cron;

use Vonnda\Subscription\Api\SubscriptionCustomerRepositoryInterface;

class ProcessOrders
{

    protected $log;
    protected $searchCriteriaBuilder;
    protected $orderRepo;
    protected $attributeSetCollection;
    protected $customerRegistry;
    protected $customerFactory;
    protected $accountManagement;
    protected $storeManager;
    protected $authService;
    protected $subscriptionManager;
    protected $eventManager;
    protected $stripeCustomerFactory;
    protected $stripeCustomerResource;
    protected $addressRepo;
    protected $addressDataFactory;
    protected $regionDataFactory;
    protected $regionFactory;
    protected $dataObjectHelper;
    protected $stripeCustomerCollectionFactory;

    protected $subscriptionCustomerRepository;

    public function __construct(
        \Psr\Log\LoggerInterface $log,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepo,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attributeSetCollection,
        \Magento\Customer\Model\CustomerRegistry $customerRegistry,
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerFactory,
        \Magento\Customer\Api\AccountManagementInterface $accountManagement,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Vonnda\Cognito\Model\AuthService $authService,
        \Vonnda\Subscription\Api\SubscriptionManagerInterface $subscriptionManager,
        \Magento\Framework\Event\Manager $eventManager,
        \StripeIntegration\Payments\Model\StripeCustomerFactory $stripeCustomerFactory,
        \StripeIntegration\Payments\Model\ResourceModel\StripeCustomer $stripeCustomerResource,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepo,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory,
        \Magento\Customer\Api\Data\RegionInterfaceFactory $regionDataFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \StripeIntegration\Payments\Model\ResourceModel\StripeCustomer\CollectionFactory $stripeCustomerCollectionFactory,
        SubscriptionCustomerRepositoryInterface $subscriptionCustomerRepository
    ) {
        $this->log = $log;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepo = $orderRepo;
        $this->attributeSetCollection = $attributeSetCollection;
        $this->customerRegistry = $customerRegistry;
        $this->customerFactory = $customerFactory;
        $this->accountManagement = $accountManagement;
        $this->storeManager = $storeManager;
        $this->authService = $authService;
        $this->subscriptionManager = $subscriptionManager;
        $this->eventManager = $eventManager;
        $this->stripeCustomerFactory = $stripeCustomerFactory;
        $this->stripeCustomerResource = $stripeCustomerResource;
        $this->addressRepo = $addressRepo;
        $this->addressDataFactory = $addressDataFactory;
        $this->regionDataFactory = $regionDataFactory;
        $this->regionFactory = $regionFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->stripeCustomerCollectionFactory = $stripeCustomerCollectionFactory;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
    }

    public function execute() {
        $orderCriteria = $this->searchCriteriaBuilder->addFilter('status', 'pending', 'eq')->create();
        $orders = $this->orderRepo->getList($orderCriteria);

        if ($orders->count() > 0) {
            $deviceSetId = $this->attributeSetCollection->create()->addFieldToSelect('*')->addFieldToFilter('attribute_set_name', 'Device')->getFirstItem()->getAttributeSetId();
            foreach ($orders as $order) { /** @var \Magento\Sales\Api\Data\OrderInterface $order */
                try {
                    switch ($this->getOrderType($order)) {
                        case 'create_subscription':
                            $this->processCreateSubscription($order);
                        break;
                        case 'gift_order':
                            $this->processGiftOrder($order);
                        break;
                        default: //a la carte
                            $this->processALaCarte($order);
                        break;
                    }
                } catch (\Exception $e) {
                    $this->log->critical($e);
                }
            }
        }
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     */
    protected function processCreateSubscription(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        if($this->orderHasSubscriptions($order)){
            $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
            $this->orderRepo->save($order);
            return;
        }
        $hideTealiumEmailPreferences = false;
        $addSubPayment = false; // the subscription payment is only added if this is true. this should only be true if the user has no previous stripe profiles
        $email = strtolower($order->getCustomerEmail());
        if ($order->getCustomerId() == null) {
            $customer = $this->getOrCreateCustomer($order, $email);
        } else { // order placed by logged in customer
            $customer = $this->customerRegistry->retrieve($order->getCustomerId());
            if ($order->getShippingAddress()->getCustomerAddressId() == null) { // if the shipping address wasnt an existing user address
                $this->createCustomerAddressFromOrder($order, $customer->getId(), $customer->getAddressesCollection()); // try to add the shipping address to the account
            }
            
            $hideTealiumEmailPreferences = true;
        }
        if ($order->getPayment()->getMethod() == 'stripe_payments') {
            $addSubPayment = true; // no need to check for stripe profile, logged in user checks out with stripe, they will have a profile associated and the order will use it
        }
        $this->subscriptionManager->processOrder($order, $customer, $addSubPayment);
        $this->eventManager->dispatch('vonnda_checkout_cron_process_after', ['order' => $order, 'customer' => $customer, 'hide_tealium_email_preferences' => $hideTealiumEmailPreferences]);
        $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
        $this->orderRepo->save($order);
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param string $email
     */
    protected function getOrCreateCustomer(\Magento\Sales\Api\Data\OrderInterface $order, $email)
    {
        $websiteId = $this->storeManager->getStore($order->getStoreId())->getWebsiteId();
        $customer = $this->accountManagement->getMagentoCustomer($email, $websiteId); // check if a customer exists in magento with the given email
        if ($customer === false) { // this means no existing magento customer, but we didn't check cognito
            $customer = $this->accountManagement->getCognitoCustomer($email, $websiteId); // now we check if a user exists in cognito, if so, the magento customer is created and returned
            if ($customer === false) { // no cognito user either, so we create a new cognito user and magento customer from scratch
                $customer = $this->customerFactory->create();
                $customer->setWebsiteId($websiteId)
                    ->setEmail($email)
                    ->setFirstname($order->getBillingAddress()->getFirstname())
                    ->setLastname($order->getBillingAddress()->getLastname());
                $customer = $this->accountManagement->createAccount($customer, null);
                $this->authService->forgotPassword($email);
            } // else cognito user existed, magento user was created and returned. in both instances, the magento customer is new, so the next steps apply to both
            $this->createCustomerAddressFromOrder($order, $customer->getId());
            if ($order->getPayment()->getMethod() == 'stripe_payments') {
                $stripeCustomer = $this->stripeCustomerFactory->create();
                $this->stripeCustomerResource->load($stripeCustomer, $order->getPayment()->getAdditionalInformation('customer_stripe_id')); // get the stripe customer for the current order
                $stripeCustomer->setCustomerId($customer->getId())->save(); // attach the new magento customer to the stripe customer
            }
        } else { // existing customer checked out as guest
            $this->createCustomerAddressFromOrder($order, $customer->getId(), $customer->getAddressesCollection());
        }
        $order->setCustomerId($customer->getId())->setCustomerIsGuest(0);
        return $customer;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     */
    protected function processGiftOrder(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        $createCustomer = false;
        $this->processALaCarte($order, $createCustomer);
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param bool $createCustomer
     */
    protected function processALaCarte(\Magento\Sales\Api\Data\OrderInterface $order, $createCustomer = false)
    {
        $email = strtolower($order->getCustomerEmail());
        if ($order->getCustomerId() == null) {
            $websiteId = $this->storeManager->getStore($order->getStoreId())->getWebsiteId();
            if ($createCustomer) {
                $customer = $this->getOrCreateCustomer($order, $email);
            } else {
                $customer = $this->accountManagement->getMagentoCustomer($email, $websiteId); // check if a customer exists in magento with the given email
            }
            if ($customer) {
                $order->setCustomerId($customer->getId())->setCustomerIsGuest(0);
            }
        } else { // order placed by logged in customer
            $customer = $this->customerRegistry->retrieve($order->getCustomerId());
        }
        $this->eventManager->dispatch('vonnda_checkout_cron_process_after', ['order' => $order, 'customer' => $customer]);
        $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
        $this->orderRepo->save($order);
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return string
     */
    protected function getOrderType(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        $deviceSetId = $this->attributeSetCollection->create()->addFieldToSelect('*')->addFieldToFilter('attribute_set_name', 'Device')->getFirstItem()->getAttributeSetId();
        if ($this->orderIsGift($order)) {
            return 'gift_order';
        }
        if ($this->orderHasDevice($order, $deviceSetId)) {
            return 'create_subscription';
        }
        return 'a_la_carte';
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return string
     */
    protected function orderIsGift(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        return $order->getGiftOrder();
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param int $deviceSetId
     * @return bool
     */
    protected function orderHasDevice($order, $deviceSetId)
    {
        foreach ($order->getAllVisibleItems() as $item) {
            if ($item->getProduct()->getAttributeSetId() == $deviceSetId) {
                return true;
            }
        }
        return false;
    }

    /**
     * Adds the shipping address from the order to the customer then sets the id of the new address to customer_address_id on the order shipping address
     * for the subscription to pull the ID from when being created.
     * The $addresses param takes a customers address collection to see if the order shipping address already exists on the customer.
     * If so it sets customer_address_id to the ID of the existing address
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param int $customerId
     * @param null|\Magento\Customer\Model\ResourceModel\Address\Collection $addresses
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function createCustomerAddressFromOrder($order, $customerId, $addresses = null)
    {
        $indexes = ['firstname' => '', 'lastname' => '', 'street' => '', 'city' => '', 'region_id' => '', 'postcode' => '', 'country_id' => '', 'telephone' => ''];
        $shippingAddressData = array_intersect_key($order->getShippingAddress()->getData(), $indexes);
        if (is_object($addresses) && $addresses->count() > 0) {
            foreach ($addresses as $address) {
                $addressData = array_intersect_key($address->getData(), $indexes);
                $addressData['street'] = is_array($addressData['street']) ? implode("\n", $addressData['street']) : $addressData['street'];
                if (empty(array_diff($shippingAddressData, $addressData))) {
                    $order->getShippingAddress()->setCustomerAddressId($address->getId());
                    return;
                }
            }
        }
        $billingAddressData = array_intersect_key($order->getBillingAddress()->getData(), $indexes);
        $shippingAsBilling = empty(array_diff($shippingAddressData, $billingAddressData)); // set to a variable to check before we start adding arrays and objects to the data
        $shippingAddressData['street'] = is_array($shippingAddressData['street']) ? $shippingAddressData['street'] : explode("\n", $shippingAddressData['street']);
        $this->updateRegionData($shippingAddressData);
        $shippingAddress = $this->addressDataFactory->create();
        $this->dataObjectHelper->populateWithArray($shippingAddress, $shippingAddressData, \Magento\Customer\Api\Data\AddressInterface::class);
        $shippingAddress->setCustomerId($customerId)->setIsDefaultShipping(true);
        if ($shippingAsBilling) { // if we ran the check here, it would break array_diff. easier to var the check than change the check to work around the arrays and objects
            $shippingAddress->setIsDefaultBilling(true);
        }
        $shippingAddress = $this->addressRepo->save($shippingAddress);
        $order->getShippingAddress()->setCustomerAddressId($shippingAddress->getId());
    }

    /**
     * @param array $address
     */
    protected function updateRegionData(&$address)
    {
        if (!empty($address['region_id'])) {
            $newRegion = $this->regionFactory->create()->load($address['region_id']);
            $address['region_code'] = $newRegion->getCode();
            $address['region'] = $newRegion->getDefaultName();
        }
        $regionData = [
            \Magento\Customer\Api\Data\RegionInterface::REGION_ID => !empty($address['region_id']) ? $address['region_id'] : null,
            \Magento\Customer\Api\Data\RegionInterface::REGION => !empty($address['region']) ? $address['region'] : null,
            \Magento\Customer\Api\Data\RegionInterface::REGION_CODE => !empty($address['region_code']) ? $address['region_code'] : null,
        ];
        $region = $this->regionDataFactory->create();
        $this->dataObjectHelper->populateWithArray($region, $regionData, \Magento\Customer\Api\Data\RegionInterface::class);
        $address['region'] = $region;
    }

    protected function orderHasSubscriptions($order)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('parent_order_id',$order->getId(),'eq')
            ->create();

        $subscriptionList = $this->subscriptionCustomerRepository->getList($searchCriteria);
        if($subscriptionList->getItems()){
            return true;
        }

        return false;
    }

}
