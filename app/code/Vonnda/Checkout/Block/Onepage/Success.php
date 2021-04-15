<?php
namespace Vonnda\Checkout\Block\Onepage;

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Model\Order\Item;

use Vonnda\Subscription\Api\Data\SubscriptionPlanInterface;
use Vonnda\Subscription\Helper\TimeDateHelper;

use Carbon\Carbon;

class Success extends \Magento\Checkout\Block\Onepage\Success
{
    const AFFIRM_FLOW_NONE = 0;
    const AFFIRM_FLOW_SINGLE_DEVICE = 1;
    const AFFIRM_FLOW_MULTIPLE_DEVICE = 2;

    /** @var string The name of the attribute set for devices */
    protected const DEVICE_ATTRIBUTE_SET_NAME = 'Device';

    protected $priceHelper;
    protected $priceCurrencyHelper;
    protected $productRepository;
    protected $orderRepository;
    protected $customerSession;
    protected $attributeSetCollection;
    protected $deviceHelper;
    protected $subscriptionPlanRepository;
    protected $searchCriteriaBuilder;
    protected $helper;
    protected $tealiumHelper;
    protected $order;
    protected $timeDateHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrencyHelper
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attributeSetCollection
     * @param \Vonnda\Subscription\Helper\DeviceHelper $deviceHelper
     * @param \Vonnda\Subscription\Model\SubscriptionPlanRepository $subscriptionPlanRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Vonnda\Checkout\Helper\Data $helper
     * @param \Vonnda\TealiumTags\Helper\Data $tealiumHelper
     * @param \Vonnda\Subscription\Helper\TimeDateHelper $timeDateHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrencyHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attributeSetCollection,
        \Vonnda\Subscription\Helper\DeviceHelper $deviceHelper,
        \Vonnda\Subscription\Model\SubscriptionPlanRepository $subscriptionPlanRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Vonnda\Checkout\Helper\Data $helper,
        \Vonnda\TealiumTags\Helper\Data $tealiumHelper,
        TimeDateHelper $timeDateHelper,
        array $data = []
    ) {
        $this->priceHelper = $priceHelper;
        $this->priceCurrencyHelper = $priceCurrencyHelper;
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
        $this->customerSession = $customerSession;
        $this->attributeSetCollection = $attributeSetCollection;
        $this->deviceHelper = $deviceHelper;
        $this->subscriptionPlanRepository = $subscriptionPlanRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->helper = $helper;
        $this->tealiumHelper = $tealiumHelper;
        $this->timeDateHelper = $timeDateHelper;

        parent::__construct($context, $checkoutSession, $orderConfig, $httpContext, $data);
    }

    /**
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function getOrder()
    {
        if (!isset($this->order)) {
            $orderId = $this->_checkoutSession->getLastOrderId();
            $this->order = $this->orderRepository->get($orderId);
        }
        return $this->order;
    }

    public function getPriceHelper()
    {
        return $this->priceHelper;
    }

    public function currency($value, $format = true, $includeContainer = true)
    {
        return $this->priceHelper->currency($value, $format, $includeContainer);
    }

    public function zeroPrecisionCurrency($value, $includeContainer = true)
    {
        return $this->priceCurrencyHelper->convertAndFormat($value, $includeContainer, 0);
    }

    public function getCustomerUid()
    {
        return $this->tealiumHelper->getCustomerUid($this->getOrder()->getCustomerId());
    }

    public function getSessionId()
    {
        return $this->tealiumHelper->getSessionId();
    }

    /**
     * @param $id
     * @return \Magento\Catalog\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProduct($id)
    {
        return $this->productRepository->getById($id);
    }

    /**
     * @return bool
     */
    public function isLoggedIn()
    {
        return $this->customerSession->isLoggedIn();
    }

    public function isSubActive()
    {
        return $this->deviceHelper->isSubActive($this->getOrder());
    }

    public function getAutoCompleteApiKey()
    {
        return $this->helper->getStoreConfig('aw_osc/general/google_places_api_key');
    }

    public function getWebsiteCode()
    {
        return $this->_storeManager->getStore()->getWebsite()->getCode();
    }

    /**
     * Whether or not a device was purchased
     *
     * @return bool
     */
    public function wasDevicePurchased()
    {
        return (bool) count($this->getDeviceItems());
    }

    /**
     * Defines which design to use for affirm flow based on how many devices are in the cart
     * Return value is one of self::AFFIRM_FLOW_NONE or self::AFFIRM_FLOW_SINGLE_DEVICE or self::AFFIRM_FLOW_MULTIPLE_DEVICE
     *
     * @return int
     */
    public function getAffirmDesignFlow()
    {
        return $this->wasPaymentNotAffirm() ? self::AFFIRM_FLOW_NONE : ((1 === count($this->getDeviceItems())) ? self::AFFIRM_FLOW_SINGLE_DEVICE : self::AFFIRM_FLOW_MULTIPLE_DEVICE);
    }

    /**
     * Filters the list of items in the cart to only the devices
     *
     * @return array
     */
    public function getDeviceItems()
    {
        return array_filter($this->getOrder()->getAllVisibleItems(), [$this, 'isItemDevice']);
    }

    /**
     * Tells if the current item is a device
     *
     * @param  Item  $item
     *
     * @return bool
     */
    public function isItemDevice($item)
    {
        $deviceSetId = $this->getDeviceSetId();
        if ($item->getProduct()->getAttributeSetId() == $deviceSetId) {
            return true;
        }
        return false;
    }

    /**
     * Returns an array of all the items used in the affirm flow
     *
     * @return array
     */
    public function getAffirmFlowItems()
    {
        /** @var \MLK\Core\Model\Sales\Order $order */
        $order = $this->getOrder();
        /** @var Item[] $items */
        $items = $order->getAllItems();

        $devices = array_filter($items, [$this, 'isItemDevice']);
        $device_ids = array_map(function ($item) {
            return $item->getId();
        }, $devices);

        $flowItems = [];
        foreach ($items as $item) {
            $parent_id = $item->getParentItemId();
            if ($parent_id && in_array($parent_id, $device_ids) && $plan = $this->getSubscriptionPlan($item->getSku())) {
                $parents = array_filter($devices, function ($device) use ($parent_id) {
                    return $device->getId() == $parent_id;
                });
                // there will always be the parent because we are only searching for items with parents
                if (count($parents)) {
                    $device = reset($parents);
                } else {
                    $device = null;
                }
                $frequency = $plan->getFrequency().' '.$plan->getFrequencyUnits().((1 == $plan->getFrequency()) ? '' : 's');
                $nextRefill = $this->_localeDate->date(strtotime('+'.$frequency))->format('m/d/Y');
                if (1 == $plan->getDuration() && $fallback = $this->getFallbackSubscriptionPlan($plan)) {
                    $frequency = $fallback->getFrequency().' '.$fallback->getFrequencyUnits().((1 == $fallback->getFrequency()) ? '' : 's');
                }
                $flowItems[] = [
                        'name' => $device ? $device->getName() : $item->getName(),
                        'price' => $this->zeroPrecisionCurrency($plan->getPlanPrice(), false),
                        'device_sku' => $plan->getDeviceSku(),
                        'item_sku' => $plan->getTriggerSku(),
                        'item_image' => $this->getAffirmFlowImage($item),
                        'quantity' => (int) $item->getQtyOrdered(),
                        'info' => $plan->getMoreInfo(),
                        'description' => $plan->getShortDescription(),
                        'frequency' => $frequency,
                        'next_refill' => $nextRefill
                ];
            }
        }

        return $flowItems;
    }

    /**
     * @return array
     */
    public function getFrequencies()
    {
        return array_map(function ($item) {
            return $item['frequency'];
        }, $this->getAffirmFlowItems());
    }

    /**
     * Returns the billing address object used in the order
     *
     * @return OrderAddressInterface
     */
    public function getOrderBillingAddress()
    {
        return $this->getOrder()->getBillingAddress();
    }

    /**
     * Returns the shipping address object used in the order
     *
     * @return OrderAddressInterface
     */
    public function getOrderShippingAddress()
    {
        return $this->getOrder()->getShippingAddress();
    }

    /**
     * Finds the subscription tier applicable to the particular sku
     *
     * @param  string  $sku
     *
     * @return SubscriptionPlanInterface|null
     */
    protected function getSubscriptionPlan(string $sku)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('trigger_sku', $sku)->addFilter('store_id', $this->getOrder()->getStoreId())->create();
        $subscriptionPlanList = $this->subscriptionPlanRepository->getList($searchCriteria)->getItems();
        foreach ($subscriptionPlanList as $plan) {
            /** @var SubscriptionPlanInterface $plan */
            return $plan;
        }
        return null;
    }

    /**
     * Finds the subscription tier applicable to the particular sku
     *
     * @param  SubscriptionPlanInterface  $plan
     *
     * @return SubscriptionPlanInterface|null
     */
    protected function getFallbackSubscriptionPlan(SubscriptionPlanInterface $plan)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('identifier', $plan->getFallbackPlan())->addFilter('store_id', $this->getOrder()->getStoreId())->create();
        $subscriptionPlanList = $this->subscriptionPlanRepository->getList($searchCriteria)->getItems();
        foreach ($subscriptionPlanList as $plan) {
            /** @var SubscriptionPlanInterface $plan */
            return $plan;
        }
        return null;
    }

    /**
     * Tells whether the payment method was affirm or not
     *
     * @return bool
     */
    public function wasPaymentAffirm()
    {
        return 'affirm_gateway' === $this->getOrder()->getPayment()->getMethod();
    }

    /**
     * Tells whether the payment method was affirm or not
     *
     * @return bool
     */
    public function wasPaymentNotAffirm()
    {
        return !$this->wasPaymentAffirm();
    }

    /**
     * @param Item $item
     *
     * @return string
     */
    protected function getAffirmFlowImage($item)
    {
        /** @var Product $product */
        $product = $item->getProduct();
        try {
            $product = $this->productRepository->getById($product->getId());
            if ($product->getData('affirm_flow_image')) {
                $attribute = $product->getResource()->getAttribute('affirm_flow_image');
                if ($attribute) {
                    $url = $attribute->getFrontend()->getUrl($product);
                    if ($url) {
                        return $url;
                    }
                }
            }
        } catch (NoSuchEntityException $e) {
            // this will only happen if the product is deleted since the order was placed
        } catch (Exception $e) {
            // a fallback for any random exceptions, such as database connection issue
        }

        return $this->_assetRepo->getUrl('Magento_Catalog::images/product/placeholder/image.jpg');
    }

    /**
     * @return mixed
     */
    protected function getDeviceSetId()
    {
        static $deviceSetId = null;
        if (is_null($deviceSetId)) {
            $deviceSetName = self::DEVICE_ATTRIBUTE_SET_NAME;
            $deviceSetId = $this->attributeSetCollection->create()->addFieldToSelect('*')->addFieldToFilter('attribute_set_name', $deviceSetName)->getFirstItem()->getAttributeSetId();
        }
        return $deviceSetId;
    }

    /**
     * @return string
     */
    public function getNextRefillDate()
    {
        $nextRefillDate = $this->timeDateHelper
            ->getNextDateFromFrequency(6, "month", "m/d/Y");
        return $nextRefillDate;
    }

}
