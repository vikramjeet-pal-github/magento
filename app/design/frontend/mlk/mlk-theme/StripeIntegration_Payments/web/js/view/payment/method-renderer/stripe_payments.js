define([
    'ko',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Ui/js/model/messageList',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer',
    'StripeIntegration_Payments/js/action/get-payment-url',
    'mage/translate',
    'mage/url',
    'jquery',
    'Magento_Checkout/js/action/place-order',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/action/redirect-on-success',
    'mage/storage',
    'mage/url',
], function (
    ko,
    Component,
    globalMessageList,
    quote,
    customer,
    getPaymentUrlAction,
    $t,
    url,
    $,
    placeOrderAction,
    additionalValidators,
    redirectOnSuccessAction,
    storage,
    urlBuilder
) {
    'use strict';

    return Component.extend({
        externalRedirectUrl: null,
        defaults: {
            template: 'StripeIntegration_Payments/payment/form',
            stripePaymentsCardSave: true,
            stripePaymentsShowApplePaySection: false,
            stripePaymentsApplePayToken: null
        },

        initObservable: function ()
        {
            this._super().observe([
                'stripePaymentsError',
                'stripePaymentsCardName',
                'stripePaymentsCardNumber',
                'stripePaymentsCardExpMonth',
                'stripePaymentsCardExpYear',
                'stripePaymentsCardVerificationNumber',
                'stripePaymentsStripeJsToken',
                'stripePaymentsApplePayToken',
                'stripePaymentsCardSave',
                'stripePaymentsSelectedCard',
                'stripePaymentsShowNewCardSection',
                'stripePaymentsShowApplePaySection',
                'stripeCreatingToken',
                'isPaymentRequestAPISupported'
            ]);

            this.stripePaymentsSelectedCard.subscribe(this.onSelectedCardChanged, this);
            /**
             * VONNDA-MODIFICATION:
             * Changed default selected card
             */
            if (!this.hasSavedCards()) {
                this.stripePaymentsSelectedCard('new_card');
                this.stripePaymentsShowNewCardSection(true);
            } else {
                for (var i = 0; i < this.config().savedCards.length; i++) {
                    this.config().savedCards[i].cardType = this.cardType(this.config().savedCards[i].brand);
                }
                var cardVal = null;
                if (window.checkoutConfig.customerLastCardUsed != null) { // select last used card
                    $.each(this.config().savedCards, function(index, card) {
                        if (card.id == window.checkoutConfig.customerLastCardUsed) {
                            cardVal = card.id + ':' + card.brand + ':' + card.last4;
                            return false;
                        }
                    });
                } else { // select first card in the list
                    $.each(this.config().savedCards, function(index, card) {
                        cardVal = card.id + ':' + card.brand + ':' + card.last4;
                        return false;
                    });
                }
                this.stripePaymentsSelectedCard(cardVal);
            }

            this.showSavedCardsSection = ko.computed(function()
            {
                return this.hasSavedCards() && this.isBillingAddressSet();
            }, this);

            this.displayAtThisLocation = ko.computed(function()
            {
                return this.config().applePayLocation == 1;
            }, this);

            this.showNewCardSection = ko.computed(function()
            {
                return this.stripePaymentsShowNewCardSection() &&
                    this.isBillingAddressSet();
            }, this);

            this.showSaveCardOption = ko.computed(function()
            {
                return this.config().showSaveCardOption && customer.isLoggedIn() && this.showNewCardSection();
            }, this);

            this.hasIcons = ko.pureComputed(function()
            {
                return (this.config().icons.length > 0);
            }, this);

            this.iconsRight = ko.pureComputed(function() {
                if (this.config().iconsLocation == "right")
                    return true;
                return false;
            }, this);

            this.securityMethod = this.config().securityMethod;

            var self = this;
            window.stripePaymentForm = this;

            if (typeof onPaymentSupportedCallbacks == 'undefined')
                window.onPaymentSupportedCallbacks = [];

            onPaymentSupportedCallbacks.push(function()
            {
                self.isPaymentRequestAPISupported(true);
                self.stripePaymentsShowApplePaySection(true);
                stripe.stripePaymentForm = self;
            });

            if (typeof onTokenCreatedCallbacks == 'undefined')
                window.onTokenCreatedCallbacks = [];

            onTokenCreatedCallbacks.push(function(token)
            {
                self.stripePaymentsStripeJsToken(token.id + ':' + token.card.brand + ':' + token.card.last4);
                self.setApplePayToken(token);
            });

            quote.billingAddress.subscribe(function (address)
            {
                stripe.paramsApplePay = this.getApplePayParams();
                stripe.quote = quote;

                if (stripe.stripeJsV3)
                    stripe.initPaymentRequestButton();
            }
            , this);

            return this;
        },

        hasSavedCards: function()
        {
            return (typeof this.config().savedCards != 'undefined'
                && this.config().savedCards != null
                && this.config().savedCards.length);
        },

        /**
         * VONNDA-MODIFICATION:
         * Added classes
         */
        onSelectedCardChanged: function(newValue)
        {
            if (newValue == 'new_card') {
                $('#step-payment-method').find('.checkout-next-step').addClass('inactive');
                this.stripePaymentsShowNewCardSection(true);
            } else {
                $('#step-payment-method').find('.checkout-next-step').removeClass('inactive');
                this.stripePaymentsShowNewCardSection(false);
            }
        },

        onCheckoutFormRendered: function()
        {
            var self = stripePaymentForm;
            initStripe({ apiKey: self.config().stripeJsKey, locale: self.config().stripeJsLocale });
        },

        isBillingAddressSet: function()
        {
            return quote.billingAddress() && quote.billingAddress().canUseForBilling();
        },

        onStripeInit: function(err)
        {
            if (err)
            {
                this.stripePaymentsError(err);
                return this.showError(this.maskError(err));
            }
            else
                this.stripePaymentsError(null);
        },

        isPlaceOrderEnabled: function()
        {
            if (this.stripePaymentsError())
                return false;

            if (this.stripeCreatingToken())
                return false;

            if (this.isBillingAddressSet())
                stripe.quote = quote;

            return this.isBillingAddressSet();
        },

        isZeroDecimal: function(currency)
        {
            var currencies = ['bif', 'djf', 'jpy', 'krw', 'pyg', 'vnd', 'xaf',
                'xpf', 'clp', 'gnf', 'kmf', 'mga', 'rwf', 'vuv', 'xof'];

            return currencies.indexOf(currency) >= 0;
        },

        isApplePayEnabled: function()
        {
            return this.config().isApplePayEnabled;
        },

        icons: function()
        {
            return this.config().icons;
        },

        getApplePayParams: function()
        {
            if (!this.isApplePayEnabled())
                return null;

            if (!this.isBillingAddressSet())
                return null;

            var amount, currency;
            if (this.config().useStoreCurrency)
            {
                currency = quote.totals().quote_currency_code;
                amount = quote.totals().grand_total + quote.totals().tax_amount;
            }
            else
            {
                currency = quote.totals().base_currency_code;
                amount = quote.totals().base_grand_total;
            }

            currency = currency.toLowerCase();

            var cents = 100;
            if (this.isZeroDecimal(currency))
                cents = 1;

            amount = Math.round(amount * cents);

            var description = quote.billingAddress().firstname + " " + quote.billingAddress().lastname;

            if (typeof customer.customerData.email != 'undefined')
                description += " <" + customer.customerData.email + ">";

            return {
                "country": quote.billingAddress().countryId,
                "currency": currency,
                "total": {
                    "label": description,
                    "amount": amount
                }
            };
        },

        beginApplePay: function()
        {
            var self = this;
            var paymentRequest = this.getApplePayParams();
            var session = Stripe.applePay.buildSession(paymentRequest, function(result, completion)
            {
                self.setApplePayToken(result.token);
                self.stripePaymentsStripeJsToken(result.token.id + ':' + result.token.card.brand + ':' + result.token.card.last4);
                completion(ApplePaySession.STATUS_SUCCESS);
            },
            function(error)
            {
                alert(error.message);
            });

            session.begin();
        },

        setApplePayToken: function(token)
        {
            if (!this.isApplePayEnabled())
                return;

            this.stripePaymentsApplePayToken(token);
        },

        resetApplePay: function()
        {
            if (!this.isApplePayEnabled())
                return;

            this.stripePaymentsApplePayToken(null);
            this.stripePaymentsStripeJsToken(null);
        },

        showApplePaySection: function()
        {
            return (this.stripePaymentsShowApplePaySection || this.isPaymentRequestAPISupported);
        },

        showApplePayButton: function()
        {
            return !this.isPaymentRequestAPISupported;
        },

        config: function()
        {
            return window.checkoutConfig.payment[this.getCode()];
        },

        isActive: function(parents)
        {
            return true;
        },

        isNewCard: function()
        {
            if (!this.hasSavedCards()) return true;
            if (this.stripePaymentsSelectedCard() == 'new_card') return true;
            return false;
        },

        maskError: function(err)
        {
            return stripe.maskError(err);
        },

        placeOrder: function()
        {
            stripe.applePaySuccess = false;

            var self = this;

            this.stripePaymentsStripeJsToken(null);
            this.stripeCreatingToken(true);
            stripe.quote = quote;
            stripe.customer = customer;

            // Use the Apple Pay token as the source
            if (this.stripePaymentsApplePayToken())
            {
                stripe.applePaySuccess = true;
                stripe.sourceId = this.stripePaymentsApplePayToken().id;
            }
            // Create a new source
            else if (this.stripePaymentsSelectedCard() == 'new_card')
                stripe.sourceId = null;
            // Use one of the selected saved cards
            else
                stripe.sourceId = stripe.cleanToken(this.stripePaymentsSelectedCard());

            createStripeToken(function(err, token, response)
            {
                self.stripeCreatingToken(false);
                if (err)
                {
                    self.showError(self.maskError(err));
                    self.resetApplePay();
                    return;
                }
                else
                {
                    self.stripePaymentsStripeJsToken(token);
                    self.placeOrderWithToken();
                }
            });
        },

        useSetupIntents: function()
        {
            return this.config().useSetupIntents;
        },

        getSetupIntentClientSecret: function(callback)
        {
            if (this.config().setupIntentClientSecret)
                return this.config().setupIntentClientSecret;

            return null;
        },

        refreshSetupIntent: function()
        {
            if (!this.useSetupIntents())
                return;

            var serviceUrl = urlBuilder.build('/rest/V1/stripe/payments/get_setup_intent', {});
            var self = this;
            self.config().setupIntentClientSecret = null;

            return storage.post(
                serviceUrl,
                null,
                false
            )
            .done(function (response)
            {
                self.config().setupIntentClientSecret = response;
            });
        },

        /**
         * Place order.
         */
        placeOrderWithToken: function (data, event)
        {
            var self = this;

            if (event) {
                event.preventDefault();
            }

            var customErrorHandler = this.handlePlaceOrderErrors.bind(this);

            if (this.validate() && additionalValidators.validate()) {
                this.isPlaceOrderActionAllowed(false);

                this.getPlaceOrderDeferredObject()
                    .fail(customErrorHandler)
                    .done(
                        function () {
                            self.afterPlaceOrder();

                            if (self.redirectAfterPlaceOrder) {
                                redirectOnSuccessAction.execute();
                            }
                        }
                    );

                return true;
            }

            return false;
        },

        /**
         * @return {*}
         */
        getPlaceOrderDeferredObject: function () {
            return $.when(
                placeOrderAction(this.getData(), this.messageContainer)
            );
        },

        handlePlaceOrderErrors: function (result)
        {
            var self = this;
            var status = result.status + " " + result.statusText;
            if (result.status == 504) {
                $('.payment-method-content .messages').find('.message-error').remove();
                var message = '<div class="order-success-block message success">Thank you for your order.<br>A confirmation email will be sent shortly.</div>';
                $('.button-wrapper.show-for-desktop').prepend(message);
                $('.aw-onestep-sidebar-content').find('.actions-toolbar').before(message);
                $('.button-wrapper.show-for-desktop, .aw-onestep-sidebar-content').find('button.checkout').remove();
            } else {
                if (stripe.isAuthenticationRequired(result.responseJSON.message)) {
                    return stripe.processNextAuthentication(function (err) {
                        if (err) {
                            self.showError(err);
                            self.resetApplePay();
                            return;
                        }
                        self.placeOrderWithToken();
                    });
                }
            }
        },

        showError: function(message)
        {
            if (this.stripePaymentsApplePayToken() && this.config().applePayLocation == 2)
            {
                document.getElementById('checkout').scrollIntoView(true);
                globalMessageList.addErrorMessage({ "message": message });
            }
            else
            {
                document.getElementById('actions-toolbar').scrollIntoView(true);
                this.messageContainer.addErrorMessage({ "message": message });
            }
        },

        // afterPlaceOrder: function()
        // {
        //     if (this.redirectAfterPlaceOrder)
        //         return;
        // },

        /**
         * VONNDA-MODIFICATION:
         * Fixed the stripePaymentsApplePayToken check, because morons left the () off, which just returned the observable and made it always evaluate to true.
         * Also added validation on the actual input, sort of. Stripe uses iframes and js to obfuscate the credit card data, but they add classes outside the iframes
         * based on what's entered in the fields so we use those to validate the input as best as possible
         */
        validate: function(elm) {
            if (this.stripePaymentsApplePayToken()) {
                return true;
            }
            if (this.isNewCard()) {
                // This check doesn't work, with OneStepCheckout at least. the validation runs before the token is created
                // if (!this.stripePaymentsStripeJsToken()) {
                //     return this.showError('Could not process card details, please try again.');
                // }
                $('#stripe-payments-card-errors').removeClass('populated').html('');
                if ($('.field-cc, .field-exp, .field-cvc').find('.stripe-elements-field.StripeElement--complete').length != 3) {
                    var msg = '';
                    if ($('.field-cc').find('.stripe-elements-field.StripeElement--invalid').length > 0) {
                        msg += '<p>Your card number is invalid.</p>';
                    }
                    // invalid exp is technically possible, but the js watches keystrokes and prepends a 0 if you try to input a month greater than 12
                    if ($('.field-cvc').find('.stripe-elements-field.StripeElement--invalid').length > 0) {
                        msg += '<p>Your card\'s security code is incomplete.</p>';
                    }
                    /**
                     * This checks if message is empty first because instead of checking if a class exists, it checks if the complete class doesn't.
                     * So a field could be marked invalid, meaning its not complete, so it would match both, and having both messages isnt helpful.
                     * The complete check handles the empty class, as well as any other general error. Not sure what else would be wrong, but as the
                     * if just below new card is looking for the complete class I wanted it to be handled somehow, just in case.
                     */
                    if (msg == '') {
                        if ($('.field-cc').find('.stripe-elements-field.StripeElement--complete').length != 1) {
                            msg += '<p>Please enter your card number.</p>';
                        }
                        if ($('.field-exp').find('.stripe-elements-field.StripeElement--complete').length != 1) {
                            msg += '<p>Please enter your card\'s expiration date.</p>';
                        }
                        if ($('.field-cvc').find('.stripe-elements-field.StripeElement--complete').length != 1) {
                            msg += '<p>Please enter your card\'s security code (CVV).</p>';
                        }
                    }
                    $('#stripe-payments-card-errors').addClass('populated').html(msg);
                    $('#card-wrapper').addClass('has-error');
                    return false;
                }
                return true;
            } else if (!this.stripePaymentsSelectedCard() || (this.stripePaymentsSelectedCard().indexOf('src_') !== 0 && this.stripePaymentsSelectedCard().indexOf('card_') !== 0 && this.stripePaymentsSelectedCard().indexOf('pm_') !== 0)) {
                this.showError('Please select a card!');
                return false;
            }
            return true;
        },

        getCode: function()
        {
            return 'stripe_payments';
        },

        shouldSaveCard: function()
        {
            return ((this.showSaveCardOption() && this.stripePaymentsCardSave()) || this.config().alwaysSaveCard);
        },

        getData: function()
        {
            var data = {
                'method': this.item.method,
                'additional_data': {
                    'cc_stripejs_token': this.stripePaymentsStripeJsToken(),
                    'cc_saved': this.stripePaymentsSelectedCard(),
                    'cc_save': this.shouldSaveCard()
                }
            };

            return data;
        },

        getCcMonthsValues: function() {
            return $.map(this.getCcMonths(), function(value, key) {
                return {
                    'value': key,
                    'month': value
                };
            });
        },

        getCcYearsValues: function() {
            return $.map(this.getCcYears(), function(value, key) {
                return {
                    'value': key,
                    'year': value
                };
            });
        },

        getCcMonths: function()
        {
            return window.checkoutConfig.payment[this.getCode()].months;
        },

        getCcYears: function()
        {
            return window.checkoutConfig.payment[this.getCode()].years;
        },

        getCvvImageUrl: function() {
            return window.checkoutConfig.payment[this.getCode()].cvvImageUrl;
        },

        getCvvImageHtml: function() {
            return '<img src="' + this.getCvvImageUrl() +
                '" alt="' + 'Card Verification Number Visual Reference' +
                '" title="' + 'Card Verification Number Visual Reference' +
                '" />';
        },
        cardType: function(code)
        {
            if (typeof code == 'undefined')
                return '';

            switch (code)
            {
                case 'visa': return "Visa";
                case 'amex': return "American Express";
                case 'mastercard': return "MasterCard";
                case 'discover': return "Discover";
                case 'diners': return "Diners Club";
                case 'jcb': return "JCB";
                case 'unionpay': return "UnionPay";
                default:
                    return code.charAt(0).toUpperCase() + Array.from(code).splice(1).join('')
            }
        }
    });
});