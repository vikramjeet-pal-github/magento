<?php

namespace Vonnda\Checkout\Cron\AffirmFlow;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Vonnda\Checkout\Model\AffirmSubscriptionFactory;
use Vonnda\Checkout\Model\ResourceModel\AffirmSubscription;
use Vonnda\Checkout\Model\ResourceModel\AffirmSubscription\Collection;
use Vonnda\Checkout\Model\ResourceModel\AffirmSubscription\CollectionFactory;
use Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface;
use Vonnda\Subscription\Api\Data\SubscriptionCustomerSearchResultInterface;
use Vonnda\Subscription\Api\SubscriptionCustomerRepositoryInterface;
use Vonnda\Subscription\Helper\StripeHelper;
use Vonnda\Subscription\Model\SubscriptionCustomer;
use Vonnda\Subscription\Model\SubscriptionPayment;
use Vonnda\Subscription\Model\SubscriptionPaymentFactory;
use Vonnda\TealiumTags\Helper\SubscriptionService;

class ProcessRefills
{
    /** @var AffirmSubscription */
    protected $resourceModel;

    /** @var CollectionFactory */
    protected $collectionFactory;

    /** @var AffirmSubscriptionFactory */
    protected $factory;

    /** @var OrderRepositoryInterface */
    protected $orderRepo;

    /** @var SubscriptionCustomerRepositoryInterface */
    protected $subscriptionCustomerRepository;

    /** @var SubscriptionPaymentFactory $subscriptionPaymentFactory */
    protected $subscriptionPaymentFactory;

    /** @var SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /** @var StripeHelper $stripeHelper */
    protected $stripeHelper;

    /** @var CustomerRepositoryInterface $customerRepository */
    protected $customerRepository;

    /** @var SubscriptionService $tealiumHelper */
    protected $tealiumHelper;

    /** @var Json */
    protected $jsonSerializer;

    public function __construct(
            AffirmSubscription $resourceModel,
            CollectionFactory $collectionFactory,
            AffirmSubscriptionFactory $factory,
            OrderRepositoryInterface $orderRepo,
            SubscriptionCustomerRepositoryInterface $subscriptionCustomerRepository,
            SubscriptionPaymentFactory $subscriptionPaymentFactory,
            SearchCriteriaBuilder $searchCriteriaBuilder,
            StripeHelper $stripeHelper,
            CustomerRepositoryInterface $customerRepository,
            SubscriptionService $tealiumHelper,
            Json $jsonSerializer
    ) {
        $this->resourceModel = $resourceModel;
        $this->collectionFactory = $collectionFactory;
        $this->factory = $factory;
        $this->orderRepo = $orderRepo;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->subscriptionPaymentFactory = $subscriptionPaymentFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->stripeHelper = $stripeHelper;
        $this->customerRepository = $customerRepository;
        $this->tealiumHelper = $tealiumHelper;
        $this->jsonSerializer = $jsonSerializer;
    }

    public function execute()
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->setOrder('id', Collection::SORT_ORDER_ASC)->setPageSize(5);
        foreach ($collection as $item) {
            /** @var \Vonnda\Checkout\Model\AffirmSubscription $item */
            $order = null;
            try {
                $order = $this->orderRepo->get($item->getOrderId());
            } catch(NoSuchEntityException $exception) {
                // if the order is not found with id, try with increment id
                $searchCriteria = $this->searchCriteriaBuilder->addFilter('increment_id', $item->getOrderId())->create();
                $orderList = $this->orderRepo->getList($searchCriteria);
                if ($orderList->getTotalCount()) {
                    foreach ($orderList->getItems() as $listItem) {
                        if ($listItem->getIncrementId() == $item->getOrderId()) {
                            $order = $listItem;
                            break;
                        }
                    }
                }
            }
            if ($order && Order::STATE_PROCESSING === $order->getStatus()) {
                $searchCriteria = $this->searchCriteriaBuilder->addFilter('parent_order_id', $order->getId())->create();
                /** @var SubscriptionCustomerSearchResultInterface $subscriptionCustomerSearchResult */
                $subscriptionCustomerSearchResult = $this->subscriptionCustomerRepository->getList($searchCriteria);
                /** @var SubscriptionCustomerInterface[] $subscriptionCustomers */
                $subscriptionCustomers = $subscriptionCustomerSearchResult->getItems();
                foreach ($subscriptionCustomers as $subscriptionCustomer) {
                    if(SubscriptionCustomer::AUTORENEW_ON_STATUS != $subscriptionCustomer->getStatus()) {
                        $card = $this->stripeHelper->getCardFromCustomerIdAndPaymentCode($subscriptionCustomer->getCustomerId(), $item->getStripeId());
                        $expirationDate = false;
                        if ($card) {
                            $expirationDate = $card->exp_month."/".$card->exp_year;
                        }

                        $subscriptionPayment = $subscriptionCustomer->getPayment();
                        if (!$subscriptionPayment) { // because payment is most likely not set
                            $subscriptionPayment = $this->subscriptionPaymentFactory->create();
                        }
                        $subscriptionPayment->setStripeCustomerId($item->getStripeCustomer());
                        $subscriptionPayment->setPaymentCode($item->getStripeId());
                        $subscriptionPayment->setExpirationDate($expirationDate ? $expirationDate : null);
                        $subscriptionPayment->setStatus(SubscriptionPayment::VALID_STATUS);
                        $subscriptionCustomer->setPayment($subscriptionPayment); // this will also save the subscription payment object

                        $subscriptionCustomer->setStatus(SubscriptionCustomer::AUTORENEW_ON_STATUS);
                        $this->subscriptionCustomerRepository->save($subscriptionCustomer);

                        $customer = $this->customerRepository->getById($subscriptionCustomer->getCustomerId());
                        $this->tealiumHelper->createAffirmFlowActivateAutoRenewEvent($customer, $subscriptionCustomer);
                    }
                }

                // now that it's processed, delete it
                $this->resourceModel->delete($item);
            }
        }
    }
}