<?php 
/**
 * @copyright: Copyright © 2020 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\GiftOrder\Model;

use Vonnda\GiftOrder\Api\GiftOrderServiceInterface;
use Vonnda\GiftOrder\Model\GiftOrderDataFactory;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteIdMaskFactory;

class GiftOrderService implements GiftOrderServiceInterface
{
    protected $searchCriteriaBuilder;

    protected $userContext;

    protected $request;

    protected $customerRepository;

    protected $storeManager;
    
    protected $scopeConfig;

    protected $appState;

    protected $quoteRepository;

    protected $quoteFactory;

    protected $giftOrderDataFactory;

    protected $quoteIdMaskFactory;

    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        UserContextInterface $userContext,
        RequestInterface $request,
        CustomerRepositoryInterface $customerRepository,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        State $appState,
        CartRepositoryInterface $quoteRepository,
        QuoteFactory $quoteFactory,
        GiftOrderDataFactory $giftOrderDataFactory,
        QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->userContext = $userContext;
        $this->request = $request;
        $this->customerRepository = $customerRepository;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->appState = $appState;
        $this->quoteRepository = $quoteRepository;
        $this->quoteFactory = $quoteFactory;
        $this->giftOrderDataFactory = $giftOrderDataFactory;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function setGiftOrderOnGuestCart(string $cartId, bool $gift_order)
    {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $quote = $this->quoteRepository->get($quoteIdMask->getQuoteId());
        
        $quote->setGiftOrder($gift_order);
        $this->quoteRepository->save($quote);
        $giftOrderData = $this->giftOrderDataFactory->create();
        $giftOrderData->setGiftOrder($gift_order);
        return $giftOrderData;
    }

    /**
     * {@inheritdoc}
     */
    public function setGiftOrderOnCart(bool $gift_order)
    {
        $customerId = $this->userContext->getUserId();
        $quote = $this->quoteFactory->create()->loadByCustomer($customerId);
        $quote->setGiftOrder($gift_order);
        $this->quoteRepository->save($quote);
        
        $giftOrderData = $this->giftOrderDataFactory->create();
        $giftOrderData->setGiftOrder($gift_order);
        return $giftOrderData;
    }

}
