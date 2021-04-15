define([
    'ko',
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/quote'
], function (ko, Component, quote) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Aheadworks_OneStepCheckout/totals/discount'
        },
        totals: quote.getTotals(),

        /**
         * Check if total displayed
         * @returns {boolean}
         */
        isDisplayed: function() {
            return this.getPureValue() != 0;
        },

        /**
         * Get coupon code
         * @returns {string|null}
         */
        getCouponCode: function() {
            if (!this.totals()) {
                return null;
            }
            return this.totals()['coupon_code'];
        },

        /**
         * Get pure total value
         * @returns {Number}
         */
        getPureValue: function() {
            if (this.totals()) {
                // separate if required. we dont want to end up in the else if this.totals is set but discount_amount is not
                if (this.totals().discount_amount) {
                    return parseFloat(this.totals().discount_amount);
                }
            } else { // to handle displaying discount on cart, where this.totals is undefined for some reason
                return window.checkoutConfig.totalsData.discount_amount;
            }
            return 0;
        },

        /**
         * Get formatted total value
         * @returns {string}
         */
        getValue: function() {
            return this.getFormattedPrice(this.getPureValue());
        }
    });
});