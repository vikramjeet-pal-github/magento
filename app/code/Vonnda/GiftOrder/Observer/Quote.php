<?php

namespace Vonnda\GiftOrder\Observer;

use Magento\Framework\Event\Observer;
use Magento\Quote\Api\CartRepositoryInterface;

class Quote implements \Magento\Framework\Event\ObserverInterface
{

    protected $quoteRepository;

    /**
     * 
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository
    ) {
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var  Quote $targetQuote */
        $targetQuote = $observer->getData('quote');
        /** @var  Quote $sourceQuote */
        $sourceQuote = $observer->getData('source');

        $giftOrder= $sourceQuote->getGiftOrder();
        $targetQuote->setGiftOrder($giftOrder);

        $shippingAddress = $sourceQuote->getShippingAddress();
        $targetQuoteShippingAddress = $targetQuote->getShippingAddress();
        if ($shippingAddress && $targetQuoteShippingAddress) {
            $giftRecipientEmail = $shippingAddress->getGiftRecipientEmail();
            $targetQuoteShippingAddress->setGiftRecipientEmail(
                $giftRecipientEmail
            );
            $targetQuote->setShippingAddress($targetQuoteShippingAddress);
        }
    }
}