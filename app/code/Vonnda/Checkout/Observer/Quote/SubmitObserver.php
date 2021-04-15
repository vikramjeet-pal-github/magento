<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vonnda\Checkout\Observer\Quote;

use Astound\Affirm\Model\Ui\ConfigProvider as AffirmConfig;

use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Framework\Event\ObserverInterface;
use Magento\GiftMessage\Model\MessageFactory;
use Magento\GiftMessage\Model\GiftMessageManager;

//This an overwrite due to double e-mails being set
class SubmitObserver implements ObserverInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var OrderSender
     */
    private $orderSender;
    /**
     * @var \Magento\Framework\DataObject\Copy
     */
    protected $objectCopyService;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $orderRepository;

    protected $giftMessageFactory;

    protected $giftMessageManager;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param OrderSender $orderSender
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        OrderSender $orderSender,
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        GiftMessageManager $giftMessageManager,
        MessageFactory $giftMessageFactory
    ) {
        $this->logger = $logger;
        $this->orderSender = $orderSender;
        $this->objectCopyService = $objectCopyService;
        $this->orderRepository = $orderRepository;
        $this->giftMessageManager = $giftMessageManager;
        $this->giftMessageFactory = $giftMessageFactory;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var  \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();
        /** @var  \Magento\Sales\Model\Order $order */

        $order = $observer->getEvent()->getOrder();

        $order->setGiftOrder($quote->getGiftOrder());

        $quoteShippingAddress = $quote->getShippingAddress();
        $orderShippingAddress = $order->getShippingAddress();
        $orderShippingAddress->setGiftRecipientEmail($quoteShippingAddress->getGiftRecipientEmail());
        $order->setShippingAddress($orderShippingAddress);
        $this->orderRepository->save($order);

        $this->setGiftMessageFields($quote, $quoteShippingAddress);

        $payment = $quote->getPayment();
        $paymentMethod = $payment->getMethod();
        $paymentIsAffirm = $paymentMethod === AffirmConfig::CODE;
        $redirectUrl = $payment->getOrderPlaceRedirectUrl();
        if (!$redirectUrl && $order->getCanSendNewEmailFlag() && !$paymentIsAffirm) {
            try {
                $this->orderSender->send($order);
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }
    }

    protected function  setGiftMessageFields($quote, $shippingAddress)
    {
        try {
            if ($quote->getGiftOrder()) {
                $messageId = $quote->getGiftMessageId();
                if (!$messageId) {
                    return null;
                }

                //Because the repo functions only get active quotes
                $giftMessage = $this->giftMessageFactory->create()->load($messageId);

                //TODO - DO WE WANT TO CREATE ONE IF NONE EXISTS?
                if(!$giftMessage){
                    return null;
                }
                $billingAddress = $quote->getBillingAddress();
                $giftMessage->setRecipient($shippingAddress->getFirstname())
                    ->setSender($billingAddress->getFirstname() ? $billingAddress->getFirstname(): NULL);
                $this->giftMessageManager->setMessage($quote, 'quote', $giftMessage);
            }
        } catch(\Exception $e){
            $this->logger->critical($e);
        }
    }
}
