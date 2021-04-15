<?php

namespace Vonnda\TealiumTags\Helper;

use Vonnda\TealiumTags\Helper\Data as DataObjectHelper;
use Vonnda\Subscription\Model\SubscriptionCustomerRepository;
use Vonnda\Subscription\Model\SubscriptionPlanRepository;

use Tealium\Tags\Helper\Product;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\Order\Item;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order\Payment\Transaction;


class TealiumData extends \Tealium\Tags\Helper\TealiumData
{
    
    const DEBUG = false;
    
    /**
     * @var \Magento\Store\Api\Data\StoreInterface $store
     */
    private $store;

    /**
     * @var \Magento\Framework\View\Element\Template $page
     */
    private $page;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var Product
     */
    protected $productHelper;

    protected $dataObjectHelper;

    protected $logger;

    protected $productRepository;

    protected $subscriberFactory;

    protected $urlInterface;

    protected $request;
    
    protected $subscriptionCustomerRepository;

    protected $subscriptionPlanRepository;

    /**
     * TealiumData constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Api\Data\StoreInterface $store
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param ProductRepositoryInterface $productRepository
     * @param UrlInterface $urlInterface
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Api\Data\StoreInterface $store,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Registry $registry,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        QuoteRepository $quoteRepository,
        Product $productHelper,
        DataObjectHelper $dataObjectHelper,
        LoggerInterface $logger,
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository,
        SubscriberFactory $subscriberFactory,
        UrlInterface $urlInterface,
        RequestInterface $request,
        SubscriptionCustomerRepository $subscriptionCustomerRepository,
        SubscriptionPlanRepository $subscriptionPlanRepository
    ) {
        parent::__construct($context, $store, $objectManager, $registry, $checkoutSession);

        $this->customerSession = $customerSession;
        $this->quoteRepository = $quoteRepository;
        $this->productHelper = $productHelper;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->subscriberFactory = $subscriberFactory;
        $this->urlInterface = $urlInterface;
        $this->request = $request;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->subscriptionPlanRepository = $subscriptionPlanRepository;
    }

    /**
     * @param $store
     */
    public function setStore($store)
    {
        $this->store = $store;
    }

    /**
     * @param \Magento\Framework\View\Element\Template $page
     */
    public function setPage($page)
    {
        $this->page = $page;
    }

    // Define methods for getting udo to output for each page type
    public function getHome()
    {
        $store = $this->store;

        /** @var \Magento\Framework\View\Element\Template $page */
        $page = $this->page;

        $outputArray = [];
        $outputArray['site_region'] = $this->_objectManager->get('Magento\Framework\Locale\Resolver')->getLocale() ?: "";

        $outputArray['site_currency'] = $store->getCurrentCurrencyCode() ?: "";

        $outputArray['page_URL'] = $this->_urlBuilder->getCurrentUrl();

        $titleBlock = $page->getLayout()->getBlock('page.main.title');
        if ($titleBlock) {
            $outputArray['page_name'] = $page->getLayout()->getBlock('page.main.title')->getPageTitle() ?: "";
            $outputArray['page_type'] = $page->getTealiumType() ?: "";
        } else {
            $outputArray['page_name'] = "not supported by extension";
            $outputArray['page_type'] = "not supported by extension";
        }

        return $outputArray;
    }

    public function getSearch()
    {
        $store = $this->store;
        $page = $this->page;
        $searchBlock = $page->getLayout()->getBlock('search.result');
        $outputArray = [];

        if ($searchBlock === false) {
            return $outputArray;
        }

        $outputArray['site_region'] = $this->_objectManager->get('Magento\Framework\Locale\Resolver')->getLocale() ?: "";
        $outputArray['site_currency'] = $store->getCurrentCurrencyCode() ?: "";
        $outputArray['page_name'] = "search results";
        $outputArray['page_type'] = "search";
        $outputArray['search_results'] = $searchBlock->getResultCount() . "" ?: "";
        $outputArray['search_keyword'] = $page->helper('Magento\CatalogSearch\Helper\Data')->getEscapedQueryText() ?: "";

        return $outputArray;
    }

    public function getCategory()
    {
        $store = $this->store;
        $page = $this->page;

        $section = false;
        $category = false;
        $subcategory = false;

        if ($_category = $this->_registry->registry('current_category')) {
//            $_category = $page->getCurrentCategory();
            $parent = false;
            $grandparent = false;

            // check for parent and grandparent
            if ($_category->getParentId()) {
                $parent =
                    $this->_objectManager->create('Magento\Catalog\Model\Category')
                        ->load($_category->getParentId());

                if ($parent->getParentId()) {
                    $grandparent =
                        $this->_objectManager->create('Magento\Catalog\Model\Category')
                            ->load($parent->getParentId());
                }
            }

            // Set the section and subcategory with parent and grandparent
            if ($grandparent) {
                $section = $grandparent->getName();
                $category = $parent->getName();
                $subcategory = $_category->getName();
            } elseif ($parent) {
                $section = $parent->getName();
                $category = $_category->getName();
            } else {
                $category = $_category->getName();
            }
        }

        $outputArray = [];
        $outputArray['site_region'] = $this->_objectManager->get('Magento\Framework\Locale\Resolver')->getLocale() ?: "";
        $outputArray['site_currency'] = $store->getCurrentCurrencyCode() ?: "";
        $outputArray['page_name'] = $_category ? ($_category->getName() ?: "") : "";
        $outputArray['page_type'] = "category";
        $outputArray['page_section_name'] = $section ?: "";
        $outputArray['page_category_name'] = $category ?: "";
        $outputArray['page_subcategory_name'] = $subcategory ?: "";

        return $outputArray;
    }

    public function getProductPage()
    {
        $store = $this->store;
        $page = $this->page;
        $_product = $this->_registry->registry('current_product');

        $outputArray = [];
        $outputArray['site_region'] = $this->_objectManager->get('Magento\Framework\Locale\Resolver')->getLocale() ?: "";
        $outputArray['site_currency'] = $store->getCurrentCurrencyCode() ?: "";
        $outputArray['page_name'] = $_product ? ($_product->getName() ?: "") : "";
        $outputArray['page_type'] = "product";

        // THE FOLLOWING NEEDS TO BE MATCHED ARRAYS (SAME NUMBER OF ELEMENTS)
        if ($_product) {
            if (!(
            $outputArray['product_id'] = [$_product->getId()]
            )) {
                $outputArray['product_id'] = [];
            }

            if (!(
            $outputArray['product_sku'] = [
                $_product->getSku()
            ]
            )) {
                $outputArray['product_sku'] = [];
            }

            if (!(
            $outputArray['product_name'] = [
                $_product->getName()
            ]
            )) {
                $outputArray['product_name'] = [];
            }

            $manufacturer = $_product->getAttributeText('manufacturer');
            if ($manufacturer === false) {
                $outputArray['product_brand'] = [""];
            } else {
                $outputArray['product_brand'] = [$manufacturer];
            }

            if (!(
            $outputArray['product_price'] = [
                number_format($_product->getFinalPrice(), 2, '.', '')
            ]
            )) {
                $outputArray['product_price'] = [];
            }

            if (!(
            $outputArray['product_list_price'] = [
                number_format($_product->getData('price'), 2, '.', '')
            ]
            )) {
                $outputArray['product_list_price'] = [];
            }
        } else {
            $outputArray['product_id'] = [];
            $outputArray['product_sku'] = [];
            $outputArray['product_name'] = [];
            $outputArray['product_brand'] = [];
            $outputArray['product_list_price'] = [];
        }

        $outputArray['product_original_price'] =
            $outputArray['product_list_price'];

        if ($this->_registry->registry('current_category')) {
            if ($this->_registry->registry('current_category')->getName()) {
                $outputArray['product_category'] = [
                    $this->_registry->registry('current_category')->getName()
                ];
            } else {
                $outputArray['product_category'] = [""];
            }
        } elseif ($_product) {
            $cats = $_product->getCategoryIds();
            if (count($cats)) {
                $firstCategoryId = $cats[0];
                $_category = $this->_objectManager->create('Magento\Catalog\Model\Category')->load($firstCategoryId);
                $outputArray['product_category'] = [
                    $_category->getName()
                ];
            } else {
                $outputArray['product_category'] = [""];
            }
        }

        return $outputArray;
    }

    public function getCartPage()
    {
        $store = $this->store;
        $page = $this->page;

        $checkout_ids =
        $checkout_skus =
        $checkout_names =
        $checkout_qtys =
        $checkout_prices =
        $checkout_original_prices =
        $checkout_brands =
            [];

        if ($this->_checkoutSession) {
            $quote = $this->_checkoutSession->getQuote();
            foreach ($quote->getAllVisibleItems() as $item) {
                $checkout_ids[] = $item->getProductId();
                $checkout_skus[] = $item->getSku();
                $checkout_names[] = $item->getName();
                $checkout_qtys[] = number_format($item->getQty(), 0, ".", "");
                $checkout_prices[] =
                    number_format($item->getPrice(), 2, ".", "");
                $checkout_original_prices[] =
                    number_format($item->getProduct()->getPrice(), 2, ".", "");
                $checkout_brands[] = $item->getProduct()->getBrand();
            }
        }

        $outputArray = [];
        $outputArray['site_region'] = $this->_objectManager->get('Magento\Framework\Locale\Resolver')->getLocale() ?: "";
        $outputArray['site_currency'] = $store->getCurrentCurrencyCode() ?: "";

        $titleBlock = $page->getLayout()->getBlock('page.main.title');
        if ($titleBlock) {
            $outputArray['page_name'] = $page->getLayout()->getBlock('page.main.title')->getPageTitle() ?: "";
            $outputArray['page_type'] = "cart";
        } else {
            $outputArray['page_name'] = "Cart";
            $outputArray['page_type'] = "cart";
        }

        // THE FOLLOWING NEEDS TO BE MATCHED ARRAYS (SAME NUMBER OF ELEMENTS)
        $outputArray['product_id'] = $checkout_ids ?: [];
        $outputArray['product_sku'] = $checkout_skus ?: [];
        $outputArray['product_name'] = $checkout_names ?: [];
        $outputArray['product_brand'] = $checkout_brands ?: [];
        $outputArray['product_category'] = [];
        $outputArray['product_quantity'] = $checkout_qtys ?: [];
        $outputArray['product_list_price'] =
            $checkout_original_prices ?: [];

        $outputArray['product_price'] = $checkout_prices ?: [];
        $outputArray['product_original_price'] =
            $outputArray['product_list_price'];

        return $outputArray;
    }

    public function getOrderConfirmation()
    {
        $orderIdArray = $this->customerSession->getTealiumCheckout();
        $this->customerSession->unsTealiumCheckout();

        $result = [];
        
        if(!$orderIdArray || (gettype($orderIdArray) === 'array' && count($orderIdArray) === 0)){
            return $result;
        }
        
        if ($this->_objectManager->create('Magento\Sales\Model\Order')) {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->_checkoutSession->getLastRealOrder();
            if ($order) {
                $result = [
                    'product_category'=>[],
                    'product_id'=>[],
                    'product_list_price'=>[],
                    'product_name'=>[],
                    'product_quantity'=>[],
                    'product_sku'=>[],
                    'product_brand' => [],
                    'product_image_url' => [],
                    'product_price' => [],
                    'product_promo_code' => [],
                    'product_discount_amount' => [],
                    'cart_id'=> $order->getQuoteId()
                ];

                $result['tealium_event'] = 'purchase';
                $result['event_category'] = "Ecommerce";
                $result['event_action'] = "Completed Transaction";
                $result['event_value'] = number_format($order->getSubtotal(), 2, '.', '');
                $result['ab_test_group'] = "";

                $result['country_code'] = $this->dataObjectHelper->getCountryFromStore();
                $result['offer_name'] = "";
                $result['page_type'] = "checkout";
                $result['page_url'] = $this->urlInterface->getCurrentUrl();

                if ($order->getPayment()) {
                    $result['payment_method'] = $order->getPayment()->getMethod();
                }

                $result = $this->dataObjectHelper->setCustomerFieldsFromOrder($result, $order);
                $result = $this->dataObjectHelper->addProductInfoFromOrderItems($result, $order);
                $result = $this->dataObjectHelper->setShippingAndBillingAddressFromOrder($result, $order);
                $result = $this->dataObjectHelper->setOrderFields($result, $order, null);
                $result = $this->dataObjectHelper->addSiteInfo($result);
                if($order->getId()){
                    $result = $this->dataObjectHelper->setIsBusinessAddressFromOrder($result, $order);
                    $result = $this->dataObjectHelper->setSubscriptionFieldsByParentOrderId($result, $order->getId());
                }
                $result = $this->getTransactionFailedData($result, $order);
            }
        }

        if(self::DEBUG){
            $this->logger->info(json_encode($result));
        }
        
        return $result;
    }

    /**
     * 
     * Modify output if purchase has failed
     * 
     * @param array $result
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    protected function getTransactionFailedData($result, $order)
    {
        $orderPayment = $order->getPayment();
        if(!$orderPayment){
            $errorMessage = "A serious error occurred, no payment set on order.";

            $result['event_action'] = "Transaction Failed";
            $result['reason_code'] = $errorMessage;
            $result['event_label'] = "Transaction Failed";
            $result['tealium_event'] = "purchase_failed";
            return $result;
        }

        if ($orderPayment->getIsTransactionPending()){
            $transactionId = $orderPayment->getTransactionId();
            $transaction = $this->transactionRepository->getByTransactionId(
                $transactionId,
                $orderPayment->getId(),
                $order->getId()
            );

            $errorMessage = $transaction->getAdditionalInformation(Transaction::RAW_DETAILS);

            $result['event_action'] = "Transaction Failed";
            $result['reason_code'] = $errorMessage;
            $result['event_label'] = "Transaction Failed";
            $result['tealium_event'] = "purchase_failed";
        }
        
        return $result;
    }

    /**
     * @param array $result
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    private function getOrderSectionData($result, $order)
    {
        $result['order_currency_code'] = $order->getBaseCurrencyCode();
        $result['order_grand_total'] = number_format($order->getGrandTotal(), 2, '.', '');

        $result['order_id'] = $order->getId();
        $result['order_id_increment'] = $order->getIncrementId();

        $result['order_promo_amount'] = number_format($order->getDiscountAmount(), 2, '.', '');
        $result['order_promo_code'] = $order->getCouponCode() ?: "";

        $result['order_shipping_amount'] = number_format($order->getShippingAmount(), 2, '.', '');
        $result['order_shipping_type'] = $order->getShippingMethod();

        $result['order_subtotal'] = number_format($order->getSubtotal(), 2, '.', '');
        $result['order_subtotal_after_promo'] = $this->dataObjectHelper->getAdjustedSubtotal($order);
        
        $result['order_tax_amount'] = number_format($order->getTaxAmount(), 2, '.', '');

        return $result;
    }

    public function getCustomerData()
    {
        $store = $this->store;
        $page = $this->page;
        $currentPath = $this->request->getPathInfo();

        $customer_id = false;
        $customer_email = false;
        $customer_type = false;

        $outputArray = [];

        $customer = $this->customerSession->getCustomer();
        if ($customer && $this->customerSession->isLoggedIn()) {
            $customer_id = $customer->getEntityId();
            $customer_email = $customer->getEmail();
            $groupId = $customer->getGroupId();
            
            $customer_type =
                $this->_objectManager->create('Magento\Customer\Model\Group')->load($groupId)->getCode();

            if($currentPath === \MLK\Core\Controller\Customer\Referrals::PATH){
                $outputArray['customer_first_name'] = $customer->getFirstname();
                $outputArray['customer_last_name'] = $customer->getLastname();
            }
        }

        $outputArray['site_region'] = $this->_objectManager->get('Magento\Framework\Locale\Resolver')->getLocale() ?: "";
        $outputArray['site_currency'] = $store->getCurrentCurrencyCode() ?: "";
        $titleBlock = $page->getLayout()->getBlock('page.main.title');
        if ($titleBlock) {
            $outputArray['page_name'] = $page->getLayout()->getBlock('page.main.title')->getPageTitle() ?: "";
            $outputArray['page_type'] = $page->getTealiumType() ?: "";
        } else {
            $outputArray['page_name'] = "Customer Data";
            $outputArray['page_type'] = "customer_data";
        }

        $outputArray['customer_id'] = $customer_id ?: "";
        $outputArray['customer_email'] = $customer_email ?: "";
        $outputArray['customer_type'] = $customer_type ?: "";

        return $outputArray;
    }

    public function getCheckoutData()
    {
        $quoteId = $this->_checkoutSession->getQuoteId();

        $result = [];

        if ($quoteId) {

            $result = [
                    'product_category'=>[],
                    'product_id'=>[],
                    'product_list_price'=>[],
                    'product_name'=>[],
                    'product_quantity'=>[],
                    'product_sku'=>[],
                    'product_brand' => [],
                    'product_image_url' => [],
                    'product_price' => [],
                    'product_promo_code' => []
            ];

            /** @var Quote $quote */
            $quote = $this->quoteRepository->get($quoteId);

            $result['tealium_event'] = "checkout";
            $result['ab_test_group'] = "";
            $result['checkout_step'] = "1";
            $result['country_code'] = $this->dataObjectHelper->getCountryFromStore();
            $result['event_action'] = "Account Creation Step";
            $result['event_category'] = "Ecommerce";
            $result['page_url'] = $this->urlInterface->getCurrentUrl();
            $result['offer_name'] = "";
            $result['page_type'] = "checkout";
            $result['cart_id'] = $quoteId;
            $result['cart_url'] = $this->urlInterface->getUrl('checkout/cart');
            $result['is_logged_in'] = $this->customerSession->isLoggedIn() ? true : false;
            
            $result = $this->getQuoteCustomerSectionData($result, $quote);
            $result = $this->dataObjectHelper->addProductInfoFromQuoteItems($result, $quote);
            $result = array_merge($this->dataObjectHelper->getCartInfo(), $result);
        }

        return $result;
    }

    /**
     * @param array $result
     * @param \Magento\Quote\Model\Quote $quote
     * @return array
     */
    private function getQuoteCustomerSectionData($result, $quote)
    {
        $customer = $this->customerSession->getCustomer();
        $result['customer_email'] = $customer->getEmail() ?: "";
        $result['customer_id'] = $customer->getId() ?: "";
        $result['session_id'] = $this->customerSession->getSessionId() ?: "";
        return $result;
    }

    public function getProductCategories($product)
    {
        $categoryCollection = $product->getCategoryCollection();
        $categoryCollection->addAttributeToSelect('name');
        foreach($categoryCollection->getItems() as $category){
            if($category->getParentId() === 0){
                return [
                    "category" => $category->getName(),
                    "subcategory" => ""
                ];
            } else {
                $parent = $this->categoryRepository->get($category->getParentId());
                return [
                    "category" => $parent->getName(),
                    "subcategory" => $category->getName()
                ];
            }
        }
        return ["category" => "", "subcategory" => ""];
    }

    public function getCountryFromCountryId($countryCode)
    {
        $countryMap = [
            "US" => "United States",
            "CA" => "Canada"
        ];

        if(isset($countryMap[$countryCode])){
            return $countryMap[$countryCode];
        }

        return "";
    }
}
