/**
 * residential.js
 */
define([
    'mage/translate'
], function ($t) {
    'use strict';

    return function (Renderer) {
        return Renderer.extend({
            defaults: {
                template: 'Aheadworks_OneStepCheckout/shipping-address/address-renderer/default'
            },
            /**
             * @param {String} attribute
             * @param {Array} attributes
             * @return {Object|null}
             */
            getCustomAttribute: function (attribute, attributes) {
                var attr, index, length;

                /** @var {Number} length */
                length = attributes.length;

                /** @var {Number} index */
                for (index = 0; index < length; index += 1) {
                    /** @var {Object} attr */
                    attr = attributes[index];

                    if (attr['attribute_code'] === attribute) {
                        return attr;
                    }
                }

                return null;
            },
            /**
             * @param {String} needle
             * @param {Array} haystack
             * @return {Boolean}
             */
            hasCustomAttribute: function (needle, haystack) {
                var attr, index, length;

                /** @var {Number} length */
                length = haystack.length;

                /** @var {Number} index */
                for (index = 0; index < length; index += 1) {
                    /** @var {Object} attr */
                    attr = haystack[index];

                    if (attr['attribute_code'] === needle) {
                        return true;
                    }
                }

                return false;
            },
            /**
             * @param {Object|null} attribute
             * @return {String}
             */
            getAddressTypeLabel: function (attribute) {
                var value;

                if (typeof attribute !== 'object') {
                    return $t('Residential');
                }

                /** @var {Boolean} value */
                value = attribute.hasOwnProperty('value')
                    ? !!attribute['value']
                    : true;

                return value ? $t('Residential') : $t('Commercial');
            }
        });
    };
});
