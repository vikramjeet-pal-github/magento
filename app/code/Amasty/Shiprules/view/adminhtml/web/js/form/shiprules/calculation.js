define([
    'jquery',
    'Magento_Ui/js/form/element/select',
    'uiRegistry'
], function ($, Select, uiRegistry) {
    'use strict';

    return Select.extend({
        clearValues: function (value) {
            if (value == 0) {
                var fieldsArray = [
                    'groped_weight.weight_from',
                    'groped_weight.weight_to',
                    'groped_qty.qty_from',
                    'groped_qty.qty_to',
                    'groped_price.price_from',
                    'groped_price.price_to'
                ];
                for (var i = 0; i < fieldsArray.length; i++) {
                    uiRegistry.get('amasty_shiprules_form.amasty_shiprules_form.products.' + fieldsArray[i]).value(0);
                }
                if ($('div[data-index="products"] .rule-param-remove').length) {
                    $('div[data-index="products"] .rule-param-remove').each(function (index, element) {
                        element.click();
                    });
                }
            }
        }
    });
});