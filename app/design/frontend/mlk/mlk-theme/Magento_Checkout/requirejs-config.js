var config = {
    map: {
        '*': {
            'successPage' : 'Magento_Checkout/js/view/success-page',
            'successPageForm' : 'Magento_Checkout/js/view/success/affirm-flow/form'
        }
    },
    shim: {
        'successPage' : ['jquery'],
        'successPageForm' : ['jquery', 'StripeIntegration_Payments/js/stripe_payments']
    }
};
