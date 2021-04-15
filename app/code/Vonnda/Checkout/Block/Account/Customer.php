<?php

namespace Vonnda\Checkout\Block\Account;

use Magento\Customer\Model\Url;
use Magento\Framework\App\Http\Context as HTTPContext;
use Magento\Framework\View\Element\Template\Context;

class Customer extends \Magento\Customer\Block\Account\Customer
{
    /**
     * @var Url
     */
    protected $customerUrl;

    public function __construct(Context $context, HTTPContext $httpContext, Url $customerUrl, array $data = [])
    {
        parent::__construct($context, $httpContext, $data);
        $this->customerUrl = $customerUrl;
    }

    public function isLoggedIn()
    {
        return $this->customerLoggedIn();
    }

    public function getLoginUrl()
    {
        return $this->getUrl(Url::ROUTE_ACCOUNT_LOGIN);
    }

    public function getAccountUrl()
    {
        return $this->customerUrl->getAccountUrl();
    }
}