define(
    [
        'uiComponent'
    ],
    function (Component) {
        'use strict';
        var quoteItemData = window.checkoutConfig.quoteItemData;

        return Component.extend({
            defaults: {
                template: 'Aheadworks_OneStepCheckout/sidebar/item-details/short-description'
            },

            getValue: function(quoteItem) {
                return quoteItem.name;
            },

            getShortDescription: function(quoteItemId) {
                var item = this.getItem(quoteItemId);
                if (typeof item === 'object' && item !== null) {
                    if(item.is_virtual == 1) {
                        var date = new Date();
                        var options = { month: 'numeric', day: 'numeric', year: 'numeric' };
                        date.setMonth(date.getMonth() + 6);
                        return '<span class="payment-cc">Next refill ' + date.toLocaleDateString('en-US', options) + '</span><span class="payment-finance">Requires sign-up after purchase</span>';
                    } else {
                        return item.product.short_description;
                    }
                } else {
                    return '';
                }
            },

            getItem: function(item_id) {
                var itemElement = null;
                _.each(quoteItemData, function(element, index) {
                    if (element.item_id == item_id) {
                        itemElement = element;
                    }
                });
                return itemElement;
            },


            getProductBasePrice: function(quoteItemId) {
                var item = this.getItem(quoteItemId);
                if(item !== null){
                    return item.base_price;
                } else {
                    return '';
                }
            },

            getProductSku: function(quoteItemId) {
                var item = this.getItem(quoteItemId);
                if(item !== null){
                    return item.product_sku;
                } else {
                    return '';
                }
            }
        });
    }
);