<?php
namespace Vonnda\TealiumTags\Observer;

use Vonnda\TealiumTags\Helper\Data as DataObjectHelper;
use Vonnda\TealiumTags\Model\HttpGateway;
use Vonnda\Subscription\Model\SubscriptionCustomerRepository;
use Vonnda\Subscription\Model\SubscriptionPlanRepository;
use Vonnda\Subscription\Helper\TimeDateHelper;
use Vonnda\Subscription\Helper\Data as SubscriptionHelper;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Checkout\Model\Session as CheckoutSession;

class OrderProcess implements ObserverInterface
{
    protected $httpGateway;

    protected $dataObjectHelper;

    protected $customerRepository;

    protected $logger;

    protected $customerSession;

    protected $subscriptionCustomerRepository;

    protected $subscriptionPlanRepository;

    protected $subscriptionHelper;

    protected $timeDateHelper;

    protected $searchCriteriaBuilder;

    protected $checkoutSession;

    public function __construct(
        HttpGateway $httpGateway,
        DataObjectHelper $dataObjectHelper,
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger,
        CustomerSession $customerSession,
        SubscriptionCustomerRepository $subscriptionCustomerRepository,
        SubscriptionPlanRepository $subscriptionPlanRepository,
        SubscriptionHelper $subscriptionHelper,
        TimeDateHelper $timeDateHelper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CheckoutSession $checkoutSession
    ) {
        $this->dataObjectHelper = $dataObjectHelper;
        $this->httpGateway = $httpGateway;
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->logger = $logger;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->subscriptionPlanRepository = $subscriptionPlanRepository;
        $this->subscriptionHelper = $subscriptionHelper;
        $this->timeDateHelper = $timeDateHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->checkoutSession = $checkoutSession;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        $utagData = [];
        $utagData['event_action'] = 'Completed Transaction';
        $utagData['event_category'] = 'Ecommerce';
        $utagData['tealium_event'] = 'purchase_api';
        $utagData['event_value'] = number_format($order->getSubtotal(), 2, '.', '');
        $utagData['cart_id'] = (string)$order->getQuoteId();
        
        $utagData['page_type'] = "checkout";
        $utagData['page_url'] = "";
        $utagData['ab_test_group'] = '';
         
        $utagData = $this->dataObjectHelper->setCustomerFieldsFromOrder($utagData, $order);

        $utagData = $this->dataObjectHelper->setShippingAndBillingAddressFromOrder($utagData, $order);

        $utagData = $this->dataObjectHelper->setOrderFields($utagData, $order, "");

        $utagData = $this->dataObjectHelper->addProductInfoFromOrderItems($utagData, $order);

        $utagData = $this->dataObjectHelper->addSiteInfo($utagData);

        if($observer->getEvent()->getHideTealiumEmailPreferences()){
            $utagData['email_preferences'] = "";
        }
        $utagData = $this->dataObjectHelper->setIsBusinessAddressFromOrder($utagData, $order);
  
        $success = $this->httpGateway->pushTag($utagData);
        if(!$success){
            $this->logger->info("Failure sending utagData for order " . $order->getId() . ", purchaseApiEvent");
        }

    }

    //We have to mimic the method in subscription manager, since the subscription may not be created yet
    //These have been moved to the subdata_event.
    protected function setSubscriptionFields($order, $utagData)
    {
        $utagData["filter_frequency"] = [];
        $utagData["next_shipment_date"] = [];
        $utagData["filter_plan_price"] = [];
        
        $itemCollection = $order->getAllItems();
        foreach($itemCollection as $item){
            if($item->getProductType() === 'virtual'){
                for($x=0; $x < $item->getQtyOrdered(); $x++){
                    $utagData = $this->processVirtualItem($order, $item,  $utagData);
                }
            }
        }

        return $utagData;
    }

    protected function processVirtualItem($order, $item, $utagData)
    {
        $itemSku = $item->getSku();
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('trigger_sku',$itemSku,'eq')
            ->addFilter('store_id',$order->getStoreId(),'eq')
            ->create();
        try {
            $subscriptionPlanList = $this->subscriptionPlanRepository->getList($searchCriteria)->getItems();
            $subscriptionPlan = $this->subscriptionHelper->returnFirstItem($subscriptionPlanList);
            if($subscriptionPlan){
                $frequency = $subscriptionPlan->getFrequency();
                $frequencyUnits = $subscriptionPlan->getFrequencyUnits();
                $nextOrderDate = $this->timeDateHelper->getNextDateFromFrequency($frequency, $frequencyUnits);
                $utagData["filter_frequency"][] = $frequency;
                $utagData["next_shipment_date"][] = $nextOrderDate;
                $utagData["filter_plan_price"][] = number_format($subscriptionPlan->getPlanPrice(), 2, '.', '');
            }
        } catch(\Exception $e){
            //Add nothing
        }

        return $utagData;
    }
}
