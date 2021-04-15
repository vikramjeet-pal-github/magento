define([
    'jquery',
    'mage/storage',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Customer/js/model/customer',
    'Aheadworks_OneStepCheckout/js/action/get-sections-details',
    'Aheadworks_OneStepCheckout/js/model/gift-message-service',
    'Magento_Ui/js/model/messageList',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/full-screen-loader'
], function (
    $,
    storage,
    urlBuilder,
    customer,
    getSectionsDetailsAction,
    giftMessageService,
    messageList,
    errorProcessor,
    quote,
    fullScreenLoader
) {
    'use strict';

    return function(itemId, giftMessage) {
        var serviceUrl;

        if (customer.isLoggedIn()) {
            serviceUrl = urlBuilder.createUrl('/carts/mine/gift-message', {});
            if (itemId !== 'order') {
                serviceUrl = urlBuilder.createUrl('/carts/mine/gift-message/:itemId', {itemId: itemId});
            }
        } else {
            serviceUrl = urlBuilder.createUrl('/guest-carts/:cartId/gift-message', {cartId: quote.getQuoteId()});
            if (itemId !== 'order') {
                serviceUrl = urlBuilder.createUrl(
                    '/guest-carts/:cartId/gift-message/:itemId',
                    {cartId: quote.getQuoteId(), itemId: itemId}
                );
            }
        }
        messageList.clear();
        fullScreenLoader.startLoader();

        var attempts = 0;
        var deferred = $.Deferred();
        var checkGiftMessage = function() {
            storage.post(serviceUrl, JSON.stringify({'gift_message': giftMessage}))
            .always(function(response) {
                getSectionsDetailsAction(['giftMessage'])
                .always(function(response) {
                    if (!response.hasOwnProperty('gift_message') ||
                        !response.gift_message.hasOwnProperty('order_message') ||
                        !response.gift_message.order_message.hasOwnProperty('message') ||
                        !response.gift_message.order_message.message.hasOwnProperty('gift_message_id') ||
                        typeof response.gift_message.order_message.message.gift_message_id != 'number'
                    ) {
                        attempts++;
                        if (attempts < 3) {
                            checkGiftMessage();
                        } else {
                            messageList.addErrorMessage({message: 'There was a problem saving your gift message. Please try again.'});
                            fullScreenLoader.stopLoader();
                            deferred.reject(false);
                        }
                    } else {
                        fullScreenLoader.stopLoader();
                        deferred.resolve(response);
                    }
                });
            });
        };
        checkGiftMessage();
        return deferred.promise();
    };
});
