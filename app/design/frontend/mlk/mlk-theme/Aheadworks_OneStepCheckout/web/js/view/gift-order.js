define([
    'ko',
    'uiComponent',
    'Aheadworks_OneStepCheckout/js/model/gift-order'
],
function (ko, Component, subscriber) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Aheadworks_OneStepCheckout/gift-order'
        },
        isGiftOrder: subscriber.isGiftOrder,
        handleGiftOrderClick: subscriber.handleGiftOrderClick,
        deviceInCart: window.checkoutConfig.deviceInCart,
        isEnabled: true
    });
});
