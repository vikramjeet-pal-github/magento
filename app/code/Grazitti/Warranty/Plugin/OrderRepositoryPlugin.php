<?php
namespace Grazitti\Warranty\Plugin;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderExtensionInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
/**
 * Class OrderRepositoryPlugin
 */
class OrderRepositoryPlugin
{
    /**
     * Product Sku field name
     */
    const FIELD_NAME = 'product_sku';
    /**
     * Order Extension Attributes Factory
     *
     * @var OrderExtensionFactory
     */
    protected $extensionFactory;
    /**
     * OrderRepositoryPlugin constructor
     *
     * @param OrderExtensionFactory $extensionFactory
     */
    public function __construct(OrderExtensionFactory $extensionFactory)
    {
        $this->extensionFactory = $extensionFactory;
    }
    /**
     * Add "customer_feedback" extension attribute to order data object to make it accessible in API data
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $order
     *
     * @return OrderInterface
     */
    public function afterGet(OrderRepositoryInterface $subject, OrderInterface $order)
    {
        $productsku = $order->getData(self::FIELD_NAME);
        $extensionAttributes = $order->getExtensionAttributes();
        $extensionAttributes = $extensionAttributes ? $extensionAttributes : $this->extensionFactory->create();
        $extensionAttributes->setProductSku($productsku);
        $order->setExtensionAttributes($extensionAttributes);
        return $order;
    }
    

    /**
     * Add "custom_sales_id" extension attribute to order data object to make it accessible in API data
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderSearchResultInterface $searchResult
     *
     * @return OrderSearchResultInterface
     */
    public function afterGetList(OrderRepositoryInterface $subject, OrderSearchResultInterface $searchResult)
    {
        $orders = $searchResult->getItems();

        foreach ($orders as &$order) {
            $customSalesId = $order->getData(self::FIELD_NAME);
            $extensionAttributes = $order->getExtensionAttributes();
            $extensionAttributes = $extensionAttributes ? $extensionAttributes : $this->extensionFactory->create();
            $extensionAttributes->setProductSku($customSalesId );
            $order->setExtensionAttributes($extensionAttributes);
        }

        return $searchResult;
    }

    public function beforeSave(OrderRepositoryInterface $subject, OrderInterface $order) {
$extensionAttributes = $order->getExtensionAttributes() ?: $this->extensionFactory->create();
    if ($extensionAttributes !== null && $extensionAttributes->getProductSku() !== null) {
        $order->setProductSku($extensionAttributes->getProductSku());
    }

    return [$order];

}
}
