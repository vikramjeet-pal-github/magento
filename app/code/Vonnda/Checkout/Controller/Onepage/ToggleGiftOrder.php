<?php

namespace Vonnda\Checkout\Controller\Onepage;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;   
use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\Context;
use Magento\Quote\Api\CartRepositoryInterface;

//Toggle Gift Order
class ToggleGiftOrder extends Action
{
    /**
     * Json Factory
     *
     * @var \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    protected $resultJsonFactory;

    /**
     * Checkout Session
     *
     * @var CheckoutSession $checkoutSession
     */
    protected $checkoutSession;

    /**
     * Customer Session
     *
     * @var CustomerSession $customerSession
     */
    protected $customerSession;

    /**
     * Quote Repository
     *
     * @var CartRepositoryInterface $quoteRepository
     */
    protected $quoteRepository;


    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->quoteRepository = $quoteRepository;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $quote = $this->checkoutSession->getQuote();

        if ($quote) {
            try {
                $isGiftOrder = $quote->getGiftOrder();
                $quote->setGiftOrder(!$isGiftOrder);
                $this->quoteRepository->save($quote);

                $response = [
                    'Status' => 'success'
                ];
            } catch (\Exception $e) {
                $response = [
                    'Status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
            return $result->setData($response);
        } else {
            $response = [
                'Status' => 'error',
                'message' => 'Quote not found.'
            ];
            return $result->setData($response);
        }
    }
}
