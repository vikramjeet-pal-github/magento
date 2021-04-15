define([
    'ko',
    'uiComponent',
    'Aheadworks_OneStepCheckout/js/model/newsletter/subscriber'
],
function (ko, Component, subscriber) {
    'use strict';

    var newsletterSubscribeConfig = window.checkoutConfig.newsletterSubscribe;

    return Component.extend({
        defaults: {
            template: 'Aheadworks_OneStepCheckout/newsletter-subscriber'
        },
        isSubscribe: subscriber.subscribe,
        deviceInCart: window.checkoutConfig.deviceInCart,
        isEnabled: ko.computed(function () {
            return newsletterSubscribeConfig.isEnabled ? subscriber.isAvailableForSubscribe() : false;
        })
    });
});