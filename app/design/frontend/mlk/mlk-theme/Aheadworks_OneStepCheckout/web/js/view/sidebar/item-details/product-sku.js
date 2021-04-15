define(
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

            getItem: function(item_id) {
                var itemElement = null;
                _.each(quoteItemData, function(element, index) {
                    if (element.item_id == item_id) {
                        itemElement = element;
                    }
                });
                return itemElement;
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