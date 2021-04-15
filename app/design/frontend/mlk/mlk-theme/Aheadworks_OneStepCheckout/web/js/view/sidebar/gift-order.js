define(
    [
        'uiComponent',
        'Aheadworks_OneStepCheckout/js/model/gift-order'
    ],
    function (Component, subscriber) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Aheadworks_OneStepCheckout/sidebar/gift-order'
            },
            isGiftOrder: subscriber.isGiftOrder
        });
    }
);
