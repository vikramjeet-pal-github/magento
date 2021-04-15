<?php
namespace Vonnda\StripePayments\Block;

class Form extends \StripeIntegration\Payments\Block\Form
{

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Payment\Model\Config $paymentConfig,
        \StripeIntegration\Payments\Model\Config $config,
        \StripeIntegration\Payments\Model\StripeCustomer $stripeCustomer,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \StripeIntegration\Payments\Helper\Generic $helper,
        \StripeIntegration\Payments\Helper\SetupIntent $setupIntent,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Framework\App\State $state,
        array $data = []
    ) {
        parent::__construct($context, $paymentConfig, $config, $stripeCustomer, $productMetadata, $helper, $setupIntent, $formKey, $data);
        /*
         * in the parent class, the template is declared without an extension, which causes an error
         * setting the template to the correct path here
         */
        $this->setTemplate('StripeIntegration_Payments::form/stripe_payments.phtml');
    }

}