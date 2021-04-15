<?php

namespace Grazitti\Warranty\Observer\CheckoutCart;
use Magento\Checkout\Model\Cart\RequestQuantityProcessor;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
class UpdateCart implements \Magento\Framework\Event\ObserverInterface
{


  protected $quoteRepository;
  public function __construct(
     \Psr\Log\LoggerInterface $logger,
     CheckoutSession $checkoutSession,
     \Magento\Quote\Api\CartRepositoryInterface $quoteRepository

    ) {
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $this->checkoutSession->getQuote();
        $quoteItems = $quote->getAllVisibleItems();
        $items = $observer->getCart()->getQuote()->getItems();
        $info = $observer->getInfo()->getData();
    
        foreach($quoteItems as $item) {
                    $productSku = $item->getSku();
                    $customSku  = $item->getProductSku();
                    $item->setQty(10);
                    
        }
        
    }
}