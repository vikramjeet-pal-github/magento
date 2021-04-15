<?php

namespace Vonnda\StripePayments\Observer;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Session;

/**
 * Class CustomerLoginSuccessObserver
 */
class CustomerLoginSuccessObserver implements ObserverInterface
{
    /**
     * session
     *
     * @var Session
     */
    protected $session;

    /**
     * @param Session $session
     */
    public function __construct(
        Session $session
    ) {
        $this->session = $session;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->session->unsGuestStripeId();
        return $this;
    }
}
