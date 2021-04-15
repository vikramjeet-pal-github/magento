define(
    [
        'uiComponent',
        'Magento_Customer/js/model/customer'
    ],
    function (Component, customer) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Aheadworks_OneStepCheckout/sidebar/existing-user-fields'
            },
            isLoggedIn: customer.isLoggedIn()
        });
    }
);
