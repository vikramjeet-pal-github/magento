/**
* Copyright 2019 aheadWorks. All rights reserved.
* See LICENSE.txt for license details.
*/

define(
    [
        'jquery',
        'underscore',
        'uiRegistry',
        'Magento_Checkout/js/model/quote',
        'Aheadworks_OneStepCheckout/js/model/payment-validation-invoker',
        'Aheadworks_OneStepCheckout/js/view/form/email',

    ],
    function (
        $,
        _,
        registry,
        quote,
        paymentValidationInvoker,
        email
    ) {
        'use strict';

        return {

            /**
             * Perform overall checkout data validation
             *
             * @returns {Deferred}
             */
            validate: function () {
                var deferred = $.Deferred(),
                    isValid = true;

                if (!this._validatePassword(isValid)) {
                    isValid = false;
                }

                if (!this._validateAddresses(isValid)) {
                    isValid = false;
                }
                if (!this._validateShippingMethod(isValid)) {
                    isValid = false;
                }
                if (!this._validateDeliveryDateFormData(isValid)) {
                    isValid = false;
                }

                this._validatePaymentMethod(isValid).done(function () {
                    if (isValid) {
                        deferred.resolve();
                    }
                });

                return deferred;
            },

            /**
             * Validate password data
             * if password is not required, the password fields are removed from the form, so the validation is never added to any fields, so no need to check it.
             * return the value that was passed in, which will be true because its the first one tested.
             *
             * @param {boolean} isValid
             * @returns {Boolean}
             */
            _validatePassword: function (isValid) {
                if (window.checkoutConfig.passwordRequired) {
                    var loginFormSelector = 'form[data-role=email-with-possible-login]',
                        loginForm = $(loginFormSelector);
                    if(loginForm.length && quote.guestEmail){
                        loginForm.validation();
                        isValid = !!$(loginFormSelector + ' input[name=customer-password]').valid() && !!$(loginFormSelector + ' input[name=confirm-customer-password]').valid();
                    }
                }
                return isValid;
            },

            /**
             * Validate addresses data
             *
             * @param {boolean} isValid
             * @returns {Boolean}
             */
            _validateAddresses: function (isValid) {
                var provider = registry.get('checkoutProvider');
                _.each(['checkout.shippingAddress', 'checkout.paymentMethod.billingAddress'], function (query) {
                    var addressComponent = registry.get(query);

                    addressComponent.validate();
                    if (isValid && provider.get('params.invalid')) {
                        isValid = false;
                        addressComponent.focusInvalid();
                    }
                }, this);

                return isValid;
            },

            /**
             * Validate shipping method
             *
             * @param {boolean} isValid
             * @returns {boolean}
             */
            _validateShippingMethod: function (isValid) {
                var shippingMethodComponent = registry.get('checkout.shippingMethod'),
                    provider = registry.get('checkoutProvider');

                shippingMethodComponent.validate();
                if (isValid && provider.get('params.invalid')) {
                    isValid = false;
                    shippingMethodComponent.scrollInvalid();
                }

                return isValid;
            },

            /**
             * Validate delivery date form data
             *
             * @param {boolean} isValid
             * @returns {boolean}
             */
            _validateDeliveryDateFormData: function (isValid) {
                var deliveryDateComponent = registry.get('checkout.shippingMethod.delivery-date'),
                    provider = registry.get('checkoutProvider');

                deliveryDateComponent.validate();
                if (isValid && provider.get('params.invalid')) {
                    isValid = false;
                    deliveryDateComponent.focusInvalid();
                }

                return isValid;
            },

            /**
             * Validate payment method
             *
             * @param {boolean} isValid
             * @returns {Deferred}
             */
            _validatePaymentMethod: function (isValid) {
                var methodListComponent = registry.get('checkout.paymentMethod.methodList'),
                    methodCode,
                    methodRenderer;

                if (quote.paymentMethod()) {
                    methodCode = quote.paymentMethod().method;
                    methodRenderer = methodListComponent.getChild(methodCode);

                    return paymentValidationInvoker.invokeValidate(methodRenderer, methodCode);
                } else {
                    if (isValid && !methodListComponent.validate()) {
                        isValid = false;
                        methodListComponent.scrollInvalid();
                    }

                    return isValid
                        ? $.Deferred().resolve()
                        : $.Deferred().reject();
                }
            }
        };
    }
);
