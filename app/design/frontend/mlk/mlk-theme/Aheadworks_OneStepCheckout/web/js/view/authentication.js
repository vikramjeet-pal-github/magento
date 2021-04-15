/**
* Copyright 2019 aheadWorks. All rights reserved.
* See LICENSE.txt for license details.
*/

define(
    [
        'jquery',
        'ko',
        'Magento_Ui/js/form/form',
        'Magento_Customer/js/action/login',
        'Magento_Customer/js/model/customer',
        'mage/validation',
        'Magento_Checkout/js/model/authentication-messages',
        'Magento_Checkout/js/model/full-screen-loader',
        'Aheadworks_OneStepCheckout/js/model/checkout-data'
    ],
    function(
        $,
        ko,
        Component,
        loginAction,
        customer,
        validation,
        messageContainer,
        fullScreenLoader,
        checkoutData
    ) {
        'use strict';
        var checkoutConfig = window.checkoutConfig;

        return Component.extend({
            defaults: {
                template: 'Aheadworks_OneStepCheckout/authentication'
            },
            isCustomerLoggedIn: customer.isLoggedIn,
            forgotPasswordUrl: checkoutConfig.forgotPasswordUrl,
            autocomplete: checkoutConfig.autocomplete,
            email: ko.computed(checkoutData.getInputFieldEmailValue),
           
            /**
             * Perform login action
             *
             * @param {Object} loginForm
             */
            login: function(loginForm) {
                var loginData = {},
                    formDataArray = $(loginForm).serializeArray();

                $.each(formDataArray, function () {
                    loginData[this.name] = this.value;
                });

                if ($(loginForm).validation() && $(loginForm).validation('isValid')) {
                    fullScreenLoader.startLoader();
                    loginAction(
                        loginData,
                        checkoutConfig.checkoutUrl,
                        undefined,
                        messageContainer
                    ).always(function() {
                        fullScreenLoader.stopLoader();
                    });
                }
            }
        });
    }
);
