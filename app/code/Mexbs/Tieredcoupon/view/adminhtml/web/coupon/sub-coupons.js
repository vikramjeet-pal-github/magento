/* global $, $H */

define([
    'mage/adminhtml/grid'
], function () {
    'use strict';

    return function (config) {
        var selectedCoupons = config.selectedCoupons,
            selectedCoupons = $H(selectedCoupons),
            gridJsObject = window[config.gridJsObjectName],
            tabIndex = 1000;

        $('sub_coupon_ids').value = Object.toJSON(selectedCoupons);

        /**
         * Register Category Product
         *
         * @param {Object} grid
         * @param {Object} element
         * @param {Boolean} checked
         */
        function registerSubCoupon(grid, element, checked) {
            if (checked) {
                selectedCoupons.set(element.value,element.value);
            } else {
                selectedCoupons.unset(element.value);
            }
            $('sub_coupon_ids').value = Object.toJSON(selectedCoupons);
            grid.reloadParams = {
                'selected_products[]': selectedCoupons.keys()
            };
        }

        /**
         * Click on product row
         *
         * @param {Object} grid
         * @param {String} event
         */
        function subCouponRowClick(grid, event) {
            var trElement = Event.findElement(event, 'tr'),
                isInput = Event.element(event).tagName === 'INPUT',
                checked = false,
                checkbox = null;

            if (trElement) {
                checkbox = Element.getElementsBySelector(trElement, 'input');

                if (checkbox[0]) {
                    checked = isInput ? checkbox[0].checked : !checkbox[0].checked;
                    gridJsObject.setCheckboxChecked(checkbox[0], checked);
                }
            }
        }

        gridJsObject.rowClickCallback = subCouponRowClick;
        gridJsObject.checkboxCheckCallback = registerSubCoupon;
    };
});
