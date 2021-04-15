define(
    [
        'uiComponent'
    ],
    function (Component) {
        'use strict';
        var quoteItemData = window.checkoutConfig.quoteItemData;
        var nextRefillDate = window.nextRefillDate;

        return Component.extend({
            defaults: {
                template: 'Aheadworks_OneStepCheckout/sidebar/item-details/description'
            },

            getValue: function(quoteItem) {
                return quoteItem.name;
            },

            getDescription: function(quoteItemId) {
                var item = this.getItem(quoteItemId);
                if (typeof item === 'object' && item !== null) {
                        return item.product.description;

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

            getNextRefillInfo: function(){
                if(nextRefillDate){
                    return '<span class="payment-cc">Next refill ' + nextRefillDate + '</span>';
                }

                var date = new Date();
                var options = { month: 'numeric', day: 'numeric', year: '2-digit' };
                date.setMonth(date.getMonth() + 6);
                return '<span class="payment-cc">Next refill ' + date.toLocaleDateString('en-US', options) + '</span>';
            },

            getProductSku: function(quoteItemId) {
                var item = this.getItem(quoteItemId);
                if(item !== null){
                    return item.sku;
                } else {
                    return '';
                }
            }
        });
    }
);
