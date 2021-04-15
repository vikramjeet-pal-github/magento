/**
 * Copyright 2019 aheadWorks. All rights reserved.\nSee LICENSE.txt for license details.
 */

define(
    [
        'underscore',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/resource-url-manager',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/action/select-billing-address',
        'Aheadworks_OneStepCheckout/js/model/shipping-information/service-busy-flag',
        'Aheadworks_OneStepCheckout/js/model/same-as-shipping-flag'
    ],
    function (
        _,
        quote,
        resourceUrlManager,
        storage,
        errorProcessor,
        selectBillingAddressAction,
        serviceBusyFlag,
        sameAsShippingFlag
    ) {
        'use strict';

        var getGiftRecipientEmail = function(){
            var giftRecipientInput = document.querySelector('input[name="gift_recipient_email"]');
            if(!giftRecipientInput){
                return null;
            }
            return giftRecipientInput.value;
        }

        return function () {
            var payload;

            if (!quote.billingAddress() || !quote.isQuoteVirtual() && sameAsShippingFlag.sameAsShipping()) {
                selectBillingAddressAction(quote.shippingAddress());
            }

            var currentAttributes = quote.shippingAddress().customAttributes;

            if(currentAttributes && Array.isArray(currentAttributes)){
                var customAttributes = 
                    [...currentAttributes
                        ,{attribute_code:"gift_recipient_email", value:getGiftRecipientEmail()}];
            } else if(currentAttributes && !Array.isArray(currentAttributes)) {
                var customAttributes = {
                    ...currentAttributes, 
                    gift_recipient_email: {
                        attribute_code:"gift_recipient_email", 
                        value:getGiftRecipientEmail()}}
            } else {
                var customAttributes = 
                    [{attribute_code:"gift_recipient_email", value:getGiftRecipientEmail()}];
            }

            payload = {
                addressInformation: {
                    shipping_address: _.extend(
                        {},
                        quote.shippingAddress(),
                        {'same_as_billing': !quote.isQuoteVirtual() && sameAsShippingFlag.sameAsShipping() ? 1 : 0},
                        {customAttributes : customAttributes}
                    ),
                    billing_address: quote.billingAddress(),
                    shipping_method_code: quote.shippingMethod().method_code,
                    shipping_carrier_code: quote.shippingMethod().carrier_code
                }
            };

            serviceBusyFlag(true);

            return storage.post(
                resourceUrlManager.getUrlForSetShippingInformation(quote),
                JSON.stringify(payload)
            ).done(
                function () {
                    serviceBusyFlag(false);
                }
            ).fail(
                function (response) {
                    errorProcessor.process(response);
                }
            );
        }
    }
);
