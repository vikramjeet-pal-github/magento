/**
 * Copyright 2019 aheadWorks. All rights reserved.\nSee LICENSE.txt for license details.
 */

define(
    [
        'ko',
        'Magento_Checkout/js/model/quote',
        'Aheadworks_OneStepCheckout/js/model/estimation-data-resolver',
        'Aheadworks_OneStepCheckout/js/action/get-sections-details',
        'Magento_Checkout/js/model/shipping-service',
        'Aheadworks_OneStepCheckout/js/model/payment-methods-service',
        'Aheadworks_OneStepCheckout/js/model/totals-service'
    ],
    function (
        ko,
        quote,
        estimationDataResolver,
        getSectionsDetailsAction,
        shippingService,
        paymentMethodsService,
        totalsService
    ) {
        'use strict';

        quote.shippingAddress.subscribe(function () {
            var shippingAddress = estimationDataResolver.resolveShippingAddress();

            // make sure that all required shipping address fields are present before allowing to go forward
            if(!shippingAddress.firstname || !shippingAddress.lastname || !shippingAddress.street || !shippingAddress.city || !(shippingAddress.region || shippingAddress.regionId) || !shippingAddress.postcode || !shippingAddress.countryId || !shippingAddress.telephone) {
                return;
            }
            // all the required address fields are good, let the shipping methods load

            var sections = ['totals'],
                isNeedToUpdatePaymentMethods = estimationDataResolver.resolveBillingAddress(),
                isNeedToUpdateShippingMethods = shippingAddress && !quote.isQuoteVirtual();
            if (isNeedToUpdateShippingMethods) {
                shippingService.isLoading(true);
                sections.push('shippingMethods');
            }
            if (isNeedToUpdatePaymentMethods) {
                paymentMethodsService.isLoading(true);
                sections.push('paymentMethods');
            }
            totalsService.isLoading(true);
            getSectionsDetailsAction(sections).always(function () {
                if (isNeedToUpdateShippingMethods) {
                    shippingService.isLoading(false);
                }
                if (isNeedToUpdatePaymentMethods) {
                    paymentMethodsService.isLoading(false);
                }
                totalsService.isLoading(false);
            });
        });

        return {};
    }
);
