<?php
namespace Vonnda\StripePayments\Block\Customer;

class Cards extends \StripeIntegration\Payments\Block\Customer\Cards
{

    protected $customerSession;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \StripeIntegration\Payments\Model\StripeCustomer $stripeCustomer,
        \StripeIntegration\Payments\Helper\Generic $helper,
        \Magento\Payment\Block\Form\Cc $ccBlock,
        \StripeIntegration\Payments\Model\Config $config,
        \Magento\Customer\Model\Session $customerSession,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        parent::__construct($context, $data, $stripeCustomer, $helper, $ccBlock, $config);
    }

    public function getCustomerEmail()
    {
        return $this->customerSession->getCustomer()->getEmail();
    }

}