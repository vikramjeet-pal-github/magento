/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/**
 * @api
 */
define(
    [
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Customer/js/customer-data'
    ],
    function (storage, errorProcessor, fullScreenLoader, customerData) {
        'use strict';

        return function (serviceUrl, payload, messageContainer) {
            fullScreenLoader.startLoader();

            return storage.post(
                serviceUrl, JSON.stringify(payload)
            ).done(
                function () {
                    customerData.invalidate(['customer']);
                }
            ).fail(
                function (response) {
                    errorProcessor.process(response, messageContainer);
                }
            ).always(
                function () {
                    fullScreenLoader.stopLoader();
                }
            );
        };
    }
);
