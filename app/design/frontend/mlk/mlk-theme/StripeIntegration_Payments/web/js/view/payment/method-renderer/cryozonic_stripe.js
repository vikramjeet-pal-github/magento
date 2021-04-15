// Copyright © Cryozonic Ltd. All rights reserved.
//
// @package    StripeIntegration_Payments
// @copyright  Copyright © Cryozonic Ltd (http://cryozonic.com)
// @license    Commercial (See http://cryozonic.com/licenses/stripe.html for details)
define(
    [
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'StripeIntegration_Payments/js/action/get-payment-url',
        'mage/translate',
        'mage/url',
        'jquery',
    ],
    function (
        ko,
        Component,
        globalMessageList,
        quote,
        customer,
        getPaymentUrlAction,
        $t,
        url,
        $
    ) {
        'use strict';

        return Component.extend({
            externalRedirectUrl: null,
            defaults: {
                template: 'StripeIntegration_Payments/payment/form',
                cryozonicStripeCardSave: true,
                cryozonicStripeShowApplePaySection: false,
                cryozonicApplePayToken: null
            },

            initObservable: function ()
            {
                this._super().observe([
                    'cryozonicStripeError',
                    'cryozonicStripeCardName',
                    'cryozonicStripeCardNumber',
                    'cryozonicStripeCardExpMonth',
                    'cryozonicStripeCardExpYear',
                    'cryozonicStripeCardVerificationNumber',
                    'cryozonicStripeJsToken',
                    'cryozonicApplePayToken',
                    'cryozonicStripeCardSave',
                    'cryozonicStripeSelectedCard',
                    'cryozonicStripeShowNewCardSection',
                    'cryozonicStripeShowApplePaySection',
                    'cryozonicCreatingToken',
                    'isPaymentRequestAPISupported'
                ]);

                this.cryozonicStripeSelectedCard.subscribe(this.onSelectedCardChanged, this);
                if (!this.hasSavedCards()) {
                    this.cryozonicStripeSelectedCard('new_card');
                    this.cryozonicStripeShowNewCardSection(true);
                } else {
                    var cardVal = null;
                    if (window.checkoutConfig.customerLastCardUsed != null) {
                        $.each(this.config().savedCards, function(index, card) {
                            if (card.id == window.checkoutConfig.customerLastCardUsed) {
                                cardVal = card.id + ':' + card.brand + ':' + card.last4;
                                return false;
                            }
                        });
                    } else {
                        $.each(this.config().savedCards, function(index, card) {
                            cardVal = card.id + ':' + card.brand + ':' + card.last4;
                            return false;
                        });
                    }
                    this.cryozonicStripeSelectedCard(cardVal);
                }
                this.showSavedCardsSection = ko.computed(function()
                {
                    return this.hasSavedCards() && this.isBillingAddressSet() && !this.cryozonicApplePayToken();
                }, this);

                this.displayAtThisLocation = ko.computed(function()
                {
                    return this.config().applePayLocation == 1;
                }, this);

                this.showNewCardSection = ko.computed(function()
                {
                    return this.cryozonicStripeShowNewCardSection() && this.isBillingAddressSet() && (!this.displayAtThisLocation() || !this.cryozonicApplePayToken());
                }, this);

                this.showSaveCardOption = ko.computed(function()
                {
                    return this.config().showSaveCardOption && customer.isLoggedIn() && (this.showNewCardSection() || this.cryozonicApplePayToken());
                }, this);

                this.securityMethod = this.config().securityMethod;

                var self = this;
                window.stripePaymentForm = this;

                if (typeof onPaymentSupportedCallbacks == 'undefined')
                    window.onPaymentSupportedCallbacks = [];

                onPaymentSupportedCallbacks.push(function()
                {
                    self.isPaymentRequestAPISupported(true);
                    self.cryozonicStripeShowApplePaySection(true);
                });

                if (typeof onTokenCreatedCallbacks == 'undefined')
                    window.onTokenCreatedCallbacks = [];

                onTokenCreatedCallbacks.push(function(token)
                {
                    self.cryozonicStripeJsToken(token.id + ':' + token.card.brand + ':' + token.card.last4);
                    self.setApplePayToken(token);
                });

                quote.billingAddress.subscribe(function (address) {
                    cryozonic.paramsApplePay = this.getApplePayParams();
                    cryozonic.setAVSFieldsFrom(address);

                    if (cryozonic.stripeJsV3)
                        cryozonic.initPaymentRequestButton();
                }, this);

                return this;
            },

            hasSavedCards: function()
            {
                return (typeof this.config().savedCards != 'undefined'
                    && this.config().savedCards != null
                    && this.config().savedCards.length);
            },

            onSelectedCardChanged: function(newValue)
            {
                if (newValue == 'new_card') {
                    $('#step-payment-method').find('.checkout-next-step').addClass('inactive');
                    this.cryozonicStripeShowNewCardSection(true);
                } else {
                    $('#step-payment-method').find('.checkout-next-step').removeClass('inactive');
                    this.cryozonicStripeShowNewCardSection(false);
                }
            },

            onCheckoutFormRendered: function()
            {
                var self = stripePaymentForm;
                if (self.config().securityMethod > 0)
                    initStripe(self.config().stripeJsKey, self.config().securityMethod);
            },

            isBillingAddressSet: function()
            {
                return quote.billingAddress() && quote.billingAddress().canUseForBilling();
            },

            onStripeInit: function(err)
            {
                if (err)
                {
                    this.cryozonicStripeError(err);
                    return this.showError(this.maskError(err));
                }
                else
                    this.cryozonicStripeError(null);
            },

            isPlaceOrderEnabled: function()
            {
                if (this.cryozonicStripeError())
                    return false;

                if (this.cryozonicCreatingToken())
                    return false;

                if (this.isBillingAddressSet())
                    cryozonic.setAVSFieldsFrom(quote.billingAddress());

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
                        self.cryozonicStripeJsToken(result.token.id + ':' + result.token.card.brand + ':' + result.token.card.last4);
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
                this.cryozonicApplePayToken(token);
                this.cryozonicStripeShowApplePaySection(false);
            },

            resetApplePay: function()
            {
                this.cryozonicApplePayToken(null);
                this.cryozonicStripeShowApplePaySection(true);
                this.cryozonicStripeJsToken(null);
            },

            showApplePaySection: function()
            {
                return (this.cryozonicStripeShowApplePaySection || this.isPaymentRequestAPISupported);
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
                if (this.cryozonicStripeSelectedCard() == 'new_card') return true;
                return false;
            },

            maskError: function(err)
            {
                return cryozonic.maskError(err);
            },

            stripeJsPlaceOrder: function()
            {
                cryozonic.applePaySuccess = false;
                if (this.config().securityMethod > 0) {
                    var self = this;

                    authStripePayment(function(err) {
                        if (err) {
                            $('#cryozonic-stripe-card-errors').addClass('populated').html(function() {
                                return $(this).html()+'<p>'+self.maskError(err)+'</p>';
                            });
                            $('#card-wrapper').addClass('has-error');
                            $('.loading-mask').hide();
                            setTimeout(function() {
                                $('#step-payment-method').get(0).scrollIntoView(
                                    {
                                      behavior: 'smooth',
                                      block: 'start',
                                      inline: 'start'
                                    }
                                );
                            }, 100);
                        } else {
                            self.placeOrder();
                        }
                    });
                } else {
                    this.placeOrder(); // Stripe.js is disabled
                }
            },

            showError: function(message) {
                if (this.cryozonicApplePayToken() && this.config().applePayLocation == 2) {
                    document.getElementById('checkout').scrollIntoView(true);
                    globalMessageList.addErrorMessage({ "message": message });
                } else {
                    document.getElementById('actions-toolbar').scrollIntoView(true);
                    this.messageContainer.addErrorMessage({ "message": message });
                }
            },

            // afterPlaceOrder: function()
            // {
            //     if (this.redirectAfterPlaceOrder)
            //         return;
            // },

            /** it looks like this.showError is trying to use the magento message blocks,
             * but it doesnt look like the payment method in OneStepCheckout has its own
             * message block, so it doesn't actually do anything. commented out in case
             * that changes, or someone wants to figure out how to fix it
             */
            validate: function(elm) {
                $('#cryozonic-stripe-card-errors').html('');
                this.cryozonicStripeJsToken(null);
                this.cryozonicCreatingToken(true);
                cryozonic.setAVSFieldsFrom(quote, customer);
                cryozonic.paymentIntent = this.config().paymentIntent;
                if (this.cryozonicApplePayToken()) {
                    cryozonic.applePaySuccess = true;
                    cryozonic.sourceId = this.cryozonicApplePayToken().id;
                    return true;
                }
                if (this.isNewCard()) {
                    if (this.config().securityMethod > 0) {
                        cryozonic.sourceId = null;
                        if (!this.cryozonicStripeJsToken()) {
                            var self = this;
                            var deferred = $.Deferred();
                            createStripeToken(function(err, token, response) {
                                self.cryozonicCreatingToken(false);
                                if (err) {
                                    // self.showError(self.maskError(err));
                                    $('#cryozonic-stripe-card-errors').addClass('populated').html(function() {
                                        return $(this).html()+'<p>'+self.maskError(err)+'</p>';
                                    });
                                    $('#card-wrapper').addClass('has-error');
                                    self.resetApplePay();
                                    deferred.reject();
                                } else {
                                    self.cryozonicStripeJsToken(token);
                                    deferred.resolve();
                                }
                            });
                            return deferred;
                        } else {
                            return true;
                        }
                    } else {
                        var data = this.getData().additional_data;
                        var msg = '';
                        if (!data.cc_owner) {
                            msg += '<p>Please enter the cardholder name</p>';
                        }
                        if (!data.cc_number) {
                            msg += '<p>Please enter your card number</p>';
                        }
                        if (!data.cc_exp_month) {
                            msg += '<p>Please enter your card\'s expiration month</p>';
                        }
                        if (!data.cc_exp_year) {
                            msg += '<p>Please enter your card\'s expiration year</p>';
                        }
                        if (!data.cc_cid) {
                            msg += '<p>Please enter your card\'s security code (CVN)</p>';
                        }
                        if (msg != '') {
                            // this.showError(msg);
                            $('#cryozonic-stripe-card-errors').addClass('populated').html(msg);
                            $('#card-wrapper').addClass('has-error');
                            return false;
                        }
                        return true;
                    }
                } else {
                    if (!this.cryozonicStripeSelectedCard() || (this.cryozonicStripeSelectedCard().indexOf('src_') !== 0 && this.cryozonicStripeSelectedCard().indexOf('card_') !== 0)) {
                        // return this.showError('Please select a card');
                        $('#cryozonic-stripe-card-errors').addClass('populated').html('Please select a card');
                        return false;
                    }
                    cryozonic.sourceId = cryozonic.cleanToken(this.cryozonicStripeSelectedCard());
                    return true;
                }
            },
            validateInput: function(elm) {
                $('#cryozonic-stripe-card-errors').html('');
                if (this.cryozonicApplePayToken()) {
                    return true;
                }
                if (this.isNewCard()) {
                    if (this.config().securityMethod > 0) {
                        if ($('.field-cc, .field-exp, .field-cvc').find('.stripe-elements-field.StripeElement--complete').length != 3) {
                            var msg = '';
                            if ($('.field-cc').find('.stripe-elements-field.StripeElement--complete').length != 1) {
                                msg += '<p>Please enter your card number</p>';
                            }
                            if ($('.field-exp').find('.stripe-elements-field.StripeElement--complete').length != 1) {
                                msg += '<p>Please enter your card\'s expiration date</p>';
                            }
                            if ($('.field-cvc').find('.stripe-elements-field.StripeElement--complete').length != 1) {
                                msg += '<p>Please enter your card\'s security code (CVV)</p>';
                            }
                            $('#cryozonic-stripe-card-errors').addClass('populated').html(msg);
                            $('#card-wrapper').addClass('has-error');
                            return false;
                        } else {
                            return true;
                        }
                    } else {
                        var data = this.getData().additional_data;
                        var msg = '';
                        if (!data.cc_owner) {
                            msg += '<p>Please enter the cardholder name</p>';
                        }
                        if (!data.cc_number) {
                            msg += '<p>Please enter your card number</p>';
                        }
                        if (!data.cc_exp_month) {
                            msg += '<p>Please enter your card\'s expiration month</p>';
                        }
                        if (!data.cc_exp_year) {
                            msg += '<p>Please enter your card\'s expiration year</p>';
                        }
                        if (!data.cc_cid) {
                            msg += '<p>Please enter your card\'s security code (CVN)</p>';
                        }
                        if (msg != '') {
                            // this.showError(msg);
                            $('#cryozonic-stripe-card-errors').addClass('populated').html(msg);
                            $('#card-wrapper').addClass('has-error');
                            return false;
                        }
                        return true;
                    }
                } else {
                    if (!this.cryozonicStripeSelectedCard() || (this.cryozonicStripeSelectedCard().indexOf('src_') !== 0 && this.cryozonicStripeSelectedCard().indexOf('card_') !== 0)) {
                        // return this.showError('Please select a card');
                        $('#cryozonic-stripe-card-errors').addClass('populated').html('Please select a card');
                        return false;
                    }
                    return true;
                }
            },

            getCode: function()
            {
                return 'cryozonic_stripe';
            },

            shouldSaveCard: function()
            {
                return ((this.showSaveCardOption() && this.cryozonicStripeCardSave()) || this.config().alwaysSaveCard);
            },

            getData: function()
            {
                var data = {
                    'method': this.item.method
                };

                if (this.config().securityMethod == 0 && this.cryozonicStripeSelectedCard() && this.cryozonicStripeSelectedCard() != 'new_card')
                {
                    data.additional_data = {
                        'cc_saved': this.cryozonicStripeSelectedCard()
                    };
                }
                else if (this.config().securityMethod >= 1)
                {
                    data.additional_data = {
                        'cc_stripejs_token': this.cryozonicStripeJsToken(),
                        'cc_save': this.shouldSaveCard()
                    };
                }
                else
                {
                    data.additional_data = {
                        'cc_owner': this.cryozonicStripeCardName(),
                        'cc_number': this.cryozonicStripeCardNumber(),
                        'cc_exp_month': this.cryozonicStripeCardExpMonth(),
                        'cc_exp_year': this.cryozonicStripeCardExpYear(),
                        'cc_cid': this.cryozonicStripeCardVerificationNumber(),
                        'cc_save': this.shouldSaveCard()
                    };
                }

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
            }
        });
    }
);