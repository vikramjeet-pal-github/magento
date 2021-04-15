<?php
namespace Grazitti\Warranty\Observer;

 
use Magento\Framework\Event\ObserverInterface;
 
class SetItemDropdownAttribute implements ObserverInterface
{
    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $quoteItem = $observer->getQuoteItem();
        $product = $observer->getProduct();
        $quoteItem->setProductSku($product->getProductSku());
    }
}