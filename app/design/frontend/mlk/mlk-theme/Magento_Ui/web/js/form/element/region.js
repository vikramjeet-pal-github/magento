/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'underscore',
    'uiRegistry',
    './select',
    'Magento_Checkout/js/model/default-post-code-resolver'
], function (_, registry, Select, defaultPostCodeResolver) {
    'use strict';

    return Select.extend({
        defaults: {
            skipValidation: false,
            imports: {
                update: '${ $.parentName }.country_id:value'
            },
            disallowedStatesUS: [
                'American Samoa',
                'Armed Forces Africa',
                'Armed Forces Americas',
                'Armed Forces Canada',
                'Armed Forces Europe',
                'Armed Forces Middle East',
                'Armed Forces Pacific',
                'Federated States Of Micronesia',
                'Guam',
                'Marshall Islands',
                'Northern Mariana Islands',
                'Palau',
                'Puerto Rico',
                'Virgin Islands'
            ]
        },

        /**
         * @param {String} value
         */
        update: function (value) {
            var country = registry.get(this.parentName + '.' + 'country_id'),
                options = country.indexedOptions,
                isRegionRequired,
                option;

            if (!value) {
                return;
            }
            option = options[value];

            if (typeof option === 'undefined') {
                return;
            }

            defaultPostCodeResolver.setUseDefaultPostCode(!option['is_zipcode_optional']);

            if (this.skipValidation) {
                this.validation['required-entry'] = false;
                this.required(false);
            } else {
                if (option && !option['is_region_required']) {
                    this.error(false);
                    this.validation = _.omit(this.validation, 'required-entry');
                    registry.get(this.customName, function (input) {
                        input.validation['required-entry'] = false;
                        input.required(false);
                    });
                } else {
                    this.validation['required-entry'] = true;
                }

                if (option && !this.options().length) {
                    registry.get(this.customName, function (input) {
                        isRegionRequired = !!option['is_region_required'];
                        input.validation['required-entry'] = isRegionRequired;
                        input.validation['validate-not-number-first'] = true;
                        input.required(isRegionRequired);
                    });
                }

                this.required(!!option['is_region_required']);
            }
        },

        /**
         * Filters 'initialOptions' property by 'field' and 'value' passed,
         * calls 'setOptions' passing the result to it
         *
         * @param {*} value
         * @param {String} field
         */
        filter: function (value, field) {
            var superFn = this._super;

            if('US' === value) {
                this.filterStates(value, field);
            } else {
                superFn.call(this, value, field);
            }

            registry.get(this.parentName + '.' + 'country_id', function (country) {
                var option = country.indexedOptions[value];

                if('US' === value || 'CA' === value) {
                    this.filterStates(value, field);
                } else {
                    superFn.call(this, value, field);
                }

                if (option && option['is_region_visible'] === false) {
                    // hide select and corresponding text input field if region must not be shown for selected country
                    this.setVisible(false);

                    if (this.customEntry) {// eslint-disable-line max-depth
                        this.toggleInput(false);
                    }
                }
            }.bind(this));
        },

        /**
         * This is a duplicate of parent's filter function with the added condition of removing some specific states
         *
         * @param value
         * @param field
         */
        filterStates: function(value, field) {
            var source = this.initialOptions,
                scope = this.parentScope,
                result;
            
            var disallowedStates = this.disallowedStatesUS;

            field = field || this.filterBy.field;

            result = _.filter(source, function(item) {
                return (item[field] === value || item.value === '') && ('shippingAddress' !== scope || !_.contains(disallowedStates, item.label));
            });

            this.setOptions(result);
        }
        
    });
});

