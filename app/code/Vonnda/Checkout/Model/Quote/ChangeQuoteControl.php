<?php
declare(strict_types=1);

namespace Vonnda\Checkout\Model\Quote;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Quote\Api\ChangeQuoteControlInterface;
use Magento\Quote\Api\Data\CartInterface;

/**
 * {@inheritdoc}
 */
class ChangeQuoteControl extends \Magento\Quote\Model\ChangeQuoteControl implements ChangeQuoteControlInterface
{
    /**
     * {@inheritdoc}
     */
    public function isAllowed(CartInterface $quote): bool
    {
        return true;
    }
}
