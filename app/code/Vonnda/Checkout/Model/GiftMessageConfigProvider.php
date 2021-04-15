<?php
namespace Vonnda\Checkout\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Helper\Context;
use Magento\GiftMessage\Api\CartRepositoryInterface;
use Magento\Checkout\Model\Session;
use Magento\GiftMessage\Helper\Message as GiftMessageHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\GiftMessage\Api\Data\MessageInterface;

class GiftMessageConfigProvider implements ConfigProviderInterface
{

    protected $scopeConfiguration;
    protected $cartRepository;
    protected $checkoutSession;

    public function __construct(
        Context $context,
        CartRepositoryInterface $cartRepository,
        Session $checkoutSession
    ) {
        $this->scopeConfiguration = $context->getScopeConfig();
        $this->cartRepository = $cartRepository;
        $this->checkoutSession = $checkoutSession;
    }

    /** @inheritdoc */
    public function getConfig()
    {
        $configuration = [];
        $orderLevelGiftMsg = $this->scopeConfiguration->isSetFlag(
            GiftMessageHelper::XPATH_CONFIG_GIFT_MESSAGE_ALLOW_ORDER,
            ScopeInterface::SCOPE_STORE
        );
        $configuration['isOrderLevelGiftOptionsEnabled'] = $orderLevelGiftMsg;
        if ($orderLevelGiftMsg) {
            $orderMessages = $this->getOrderLevelGiftMessages();
            $configuration['giftMessage']['orderLevel'] = $orderMessages === null ? true : $orderMessages->getData();
        }
        return $configuration;
    }

    /**
     * Load already specified quote level gift message.
     * @return MessageInterface|null
     */
    protected function getOrderLevelGiftMessages()
    {
        $cartId = $this->checkoutSession->getQuoteId();
        return $this->cartRepository->get($cartId);
    }

}
