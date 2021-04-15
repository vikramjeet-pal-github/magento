/**
 * set-residential-option.js
 */
define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote'
], function ($, wrapper, quote) {
    'use strict';

    return function (setShippingInformationAction) {
        return wrapper.wrap(setShippingInformationAction, function (originalAction) {
            var shippingAddress, extensionAttributes;

            /** @var {Object} shippingAddress */
            shippingAddress = quote.shippingAddress();

            /** @var {Object} extensionAttributes */
            extensionAttributes = !!shippingAddress['extension_attributes']
                ? shippingAddress['extension_attributes']
                : {};

            shippingAddress['extension_attributes'] = extensionAttributes;
            shippingAddress['extension_attributes']['is_residential'] = shippingAddress.customAttributes['is_residential'];

            return originalAction();
        });
    };
});
