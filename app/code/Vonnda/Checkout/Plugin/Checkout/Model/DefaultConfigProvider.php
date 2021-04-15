<?php
namespace Vonnda\Checkout\Plugin\Checkout\Model;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Model\Cart\Totals\ItemConverter;
use Magento\Checkout\CustomerData\DefaultItem;
use Magento\Quote\Api\CartItemRepositoryInterface as QuoteItemRepository;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Helper\Product\ConfigurationPool;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Sales\Model\OrderRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Vonnda\Checkout\Helper\Data as CheckoutHelper;
 
class DefaultConfigProvider
{

    /** @var CheckoutSession */
    protected $checkoutSession;

    /** @var CartTotalRepositoryInterface */
    protected $cartTotalRepository;

    /** @var ItemConverter */
    protected $itemConverter;

    /** @var DefaultItem */
    protected $customerDataItem;

    /** @var QuoteItemRepository */
    protected $quoteItemRepository;

    /** @var Image */
    protected $imageHelper;

    /** @var ConfigurationPool */
    protected $configurationPool;

    /** @var CustomerSession */
    protected $customerSession;

    /** @var OrderRepository */
    protected $orderRepository;

    /** @var SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /** @var SortOrderBuilder */
    protected $sortOrderBuilder;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var CheckoutHelper */
    protected $checkoutHelper;

    public function __construct(
        CheckoutSession $checkoutSession,
        CartTotalRepositoryInterface $cartTotalRepository,
        ItemConverter $itemConverter,
        DefaultItem $customerDataItem,
        QuoteItemRepository $quoteItemRepository,
        Image $imageHelper,
        ConfigurationPool $configurationPool,
        CustomerSession $customerSession,
        OrderRepository $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        ScopeConfigInterface $scopeConfig,
        CheckoutHelper $checkoutHelper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->cartTotalRepository = $cartTotalRepository;
        $this->itemConverter = $itemConverter;
        $this->customerDataItem = $customerDataItem;
        $this->quoteItemRepository = $quoteItemRepository;
        $this->imageHelper = $imageHelper;
        $this->configurationPool = $configurationPool;
        $this->customerSession = $customerSession;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->checkoutHelper = $checkoutHelper;
    }

    public function afterGetConfig(\Magento\Checkout\Model\DefaultConfigProvider $subject, array $result) {
        foreach ($result['totalsData']['items'] as $key => $itemTotals) {
            $item = $this->checkoutSession->getQuote()->getItemById($itemTotals['item_id']);
            $result['totalsData']['items'][$key]['product_type'] = $item->getProduct()->getTypeId();
            /*
            if ($item->getProduct()->getTypeId() == 'bundle') {
                $product = $item->getProduct();
                // @var \Magento\Bundle\Model\Product\Type $typeInstance
                $typeInstance = $product->getTypeInstance();
                $selectionCollection = $typeInstance->getSelectionsCollection($typeInstance->getOptionsIds($product), $product);
                foreach ($selectionCollection as $proselection) {
                    $productsArray[$proselection->getOptionId()][] = $proselection->getProductId();
                }
                $optionsCollection = $typeInstance->getOptionsCollection($product);
                foreach ($optionsCollection as $options) {
                    if ($options->getDefaultTitle() == 'Subscription' && isset($productsArray[$options->getOptionId()])) {
                        foreach ($item->getChildren() as $child) {
                            if (in_array($child->getProductId(), $productsArray[$options->getOptionId()])) {
                                $data = $this->itemConverter->modelToDataObject($child)->__toArray();
                                $data['qty'] = $item->getQty();
                                $data['product_type'] = $child->getProduct()->getTypeId();
                                $result['totalsData']['items'][] = $data;
                                $allData = $this->customerDataItem->getItemData($child);
                                $result['imageData'][$child->getItemId()] = $allData['product_image'];
                                $quoteItemData = $child->toArray();
                                $quoteItemData['options'] = [];
                                $quoteItemData['thumbnail'] = $this->imageHelper->init($child->getProduct(), 'product_thumbnail_image')->getUrl();
                                $result['quoteItemData'][] = $quoteItemData;
                            }
                        }
                    }
                }
            }
            */
        }
        $sortOrder = $this->sortOrderBuilder
            ->setField('entity_id')
            ->setDirection(SortOrder::SORT_DESC)
            ->create();
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('customer_id', $this->customerSession->getCustomerId())
            ->addSortOrder($sortOrder)
            ->setPageSize(1)->setCurrentPage(1)
            ->create();
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderRepository->getList($searchCriteria);
        $paymentCode = null;
        if ($order->count() > 0) {
            $adtnlInfo = $order->getFirstItem()->getPayment()->getAdditionalInformation();
            if (array_key_exists('payment_code', $adtnlInfo)) {
                $paymentCode = $adtnlInfo['payment_code'];
            } elseif (array_key_exists('token', $adtnlInfo)) {
                $paymentCode = $adtnlInfo['token'];
            } elseif (array_key_exists('stripejs_token', $adtnlInfo)) {
                $paymentCode = $adtnlInfo['stripejs_token'];
            }
        }
        $result['customerLastCardUsed'] = $paymentCode;
        $result['passwordRequired'] = boolval($this->scopeConfig->getValue('aw_osc/general/require_password', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
        $result['customersMustLogin'] = boolval($this->scopeConfig->getValue('aw_osc/general/require_login', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
        $result['deviceInCart'] = $this->checkoutHelper->isDeviceInCart();
        return $result;
    }

}