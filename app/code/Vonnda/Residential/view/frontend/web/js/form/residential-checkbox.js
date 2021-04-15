/**
 * residential-checkbox.js
 */
define([
    'jquery',
    'Magento_Ui/js/form/element/single-checkbox',
    'mage/translate'
], function ($, Checkbox, $t) {
    'use strict';

    return Checkbox.extend({
        /** @property {Object} defaults */
        defaults: {
            modules: {
                company: '${ $.parentName }.company'
            }
        },
        /**
         * @return {void}
         */
        toggleCompanyField: function () {
            if (this.value()) {
                this.company().hide();
            } else {
                this.company().show();
            }
        },
        /**
         * @return {void}
         */
        onCheckedChanged: function () {
            this._super();
            this.toggleCompanyField();
        }
    });
});
