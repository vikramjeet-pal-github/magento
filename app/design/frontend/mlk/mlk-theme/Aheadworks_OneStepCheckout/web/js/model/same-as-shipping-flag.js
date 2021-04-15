define(
    [
        'ko',
        'Aheadworks_OneStepCheckout/js/model/checkout-data',
        'Aheadworks_OneStepCheckout/js/model/gift-order'
    ],
    function (ko, oscCheckoutData, giftOrder) {
        'use strict';

        var flag = ko.observable(oscCheckoutData.getSameAsShippingFlag());
        if (giftOrder.isGiftOrder()) {
            oscCheckoutData.setSameAsShippingFlag(false)
        }
        flag.subscribe(function (newValue) {
            oscCheckoutData.setSameAsShippingFlag(newValue)
        });

        return {
            sameAsShipping: flag
        };
    }
);
