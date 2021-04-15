<?php
/**
 * @copyright: Copyright © 2020 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\GiftOrder\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class GiftOrder extends Template
{

    protected $checkoutSession;

    public function __construct(
        Context $context,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context);
    }

    public function isGiftOrder()
    {
        $quote = $this->checkoutSession->getQuote();
        if($quote && $quote->getGiftOrder()){
            return true;
        }
        return false;
    }

}