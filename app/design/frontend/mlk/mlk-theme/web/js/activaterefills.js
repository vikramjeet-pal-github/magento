define([
    'jquery',
    'mage/mage',
    'mage/validation',
    'underscore',
    'moment',
    'Magento_Ui/js/modal/modal',
    'mageUtils',
    'https://maps.googleapis.com/maps/api/js?key=' + window.googleAutocompleteApiKey + '&libraries=places'
], function($, mage, mageValidation, _, moment, modal, utils) {
    'use strict';

    $.widget('mage.activateRefills', {
        options: {},

        _create: function() {
            this._chosenSubscription = null;
            this._shippingAddressId = null;
            this._customerId = window.customerId;
            this._chosenPayment = null;
            this._promos = [];
            this._editingPayment = {subscriptionId: null};
            this._ccFieldsCompleted = {
                cardNumber: false,
                cardExpiry: false,
                cardCvc: false
            };
            //This is for the payment billing address
            this._addressComplete = false;
            this._newRegistration = null;

            this._overrideStripeSave();
            this._initAllGoogleAutoComplete();
            this._initializeEventListeners();

            var modalOptions = {
                'type': 'popup',
                'modalClass': 'agreements-modal',
                'title': 'Molekule Filters, Automatic Refills and Account Portal',
                'responsive': true,
                'innerScroll': true,
                'buttons': []
            };

            this._bindModal();
            modal(modalOptions, $('.js-terms-and-conditions-content'));
            var widget = this;
           
            var newRegistration = mage.cookies.get('device_registration_success');
            if (newRegistration) {
                this._newRegistration = newRegistration;
                $('.js-registered-banner').removeClass('hide');
                $('.js-device-registered-banner-button').attr('data-subscriptionid', newRegistration);
                $(document.body).on('click', '.js-device-registered-banner-button', function() {
                    var subscriptionId = $(this).attr('data-subscriptionid');
                    if(!widget._setChosenSubscription(subscriptionId)){
                        return console.error("Subscription not found");
                    };

                    $('.subscription-list__list-container, .subscription-flow').toggleClass('hide');
                    $('.page.messages').addClass('flow-active');
                    widget._showAppropriateIntroAndFpBlocks(subscriptionId);
                });
                window.onunload = function() {
                    mage.cookies.clear('device_registration_success');
                };
            }
        },

        _initializeEventListeners: function() {
            var widget = this;
            // Next button click for each step
            $(document.body).on('click', '.steps .checkout-next-step', function() {
                if ($(this).hasClass('active')) {
                    $(this).closest('.subscription-flow-step').removeClass('active').addClass('done');
                    $(this).closest('.subscription-flow-step').next('.subscription-flow-step').addClass('active');
                    widget._togglePreview($(this));
                }
                widget._enableOrDisableActivateButton();
            });
            // Edit Step
            $(document.body).on('click', '.checkout-edit-step', function() {
                $('.subscription-flow-step').removeClass('active');
                $(this).closest('.subscription-flow-step').next().removeClass('done');
                $(this).closest('.subscription-flow-step').next().next().removeClass('done');
                $(this).closest('.subscription-flow-step').addClass('active').removeClass('done');
                widget._togglePreview($(this));
            });
            // New Shipping Address
            $(document.body).on('click', '.subscription-flow-address-add', function() {
                $(this).toggleClass('active');
                $(this).next('.subscription-flow-new-address').toggle();
            });
            // New Billing Address
            $(document.body).on('change', '.js-sameasshipping input[type="checkbox"]', function() {
                if ($(this).is(':checked')) {
                    $('.subscription-flow-new-billing-address').hide();
                } else {
                    $('.subscription-flow-new-billing-address').show();
                }
                widget._updatePaymentSaveButtonStatus();
            });
            $('.js-flow-add-shipping-address').on('click', function(event) {
                event.preventDefault();
                if ($(this).closest('form').validation('isValid') && $('input#flow-shipping-telephone').val().length > 13) {
                    var addressFields = {};
                    $('.js-activate-shipping-address-input').each(function() {
                        addressFields[$(this).attr('name').replace('flow-', '')] = $(this).val();
                    });
                    addressFields['country_id'] = $('#flow-country').find('option:selected').val();
                    addressFields['region_id'] = $('.js-subscription-flow-add-address-form #state').find('option:selected').val();
                    $.ajax({
                        type: 'POST',
                        url: window.baseUrl + 'subscription/customer/addcustomeraddress',
                        showLoader: true,
                        data: {
                            addressFields: addressFields,
                            customerId: widget._customerId
                        }
                    }).success(function(data) {
                        $('.subscription-list__shipping-address-form input').not(':button, :submit, :reset, :hidden').val('').trigger('change');
                        window.addresses[data.address.id] = data.address;
                        window.addresses[data.address.id]['state'] = data.address.region;
                        $('.subscription-flow-address-add').toggleClass('active');
                        $('.subscription-flow-address-add').next('.subscription-flow-new-address').toggle();
                        $('.js-activate-shipping-address-list-item').removeClass('active');
                        
                        var listItem = $('<div>')
                            .addClass('subscription-list__shipping-address-edit-list-item')
                            .addClass('js-activate-shipping-address-list-item')
                            .data('addressid', data.address.id)
                            .append($('<p>').html(data.address.firstname + " " + addressFields.lastname))
                            .append($('<p>').html(data.address.streetOne))
                            .append($('<p>').html(data.address.streetTwo))
                            .append($('<p>').html(data.address.city + ", " + data.address.regioncode + " " + data.address.postcode))
                            .append($('<p>').html(data.address.telephone))
                            .addClass('active');
                        $('.js-activate-shipping-address-list').prepend(listItem);
                        widget._shippingAddressId = data.address.id;
                        $('.js-shipping-next').addClass('active');
                    }).error(function(err) {
                        alert('There was an error saving the shipping address');
                        console.log(err);
                    });
                } else {
                    $('input#flow-shipping-telephone').addClass('mage-error');
                    $('<div class="mage-error" generated="true">Please enter valid phone number.</div>').insertAfter('input#flow-shipping-telephone');
                }
            });

            $(document.body).on('click', '.js-activate-shipping-address-list-item', function() {
                $('.js-activate-shipping-address-list-item').removeClass('active');
                $(this).addClass('active').closest('.subscription-flow-step').find('.checkout-next-step').addClass('active');
                widget._shippingAddressId = $(this).data('addressid');
                widget._enableOrDisableActivateButton();
            });
            $(document.body).on('click', '.js-activate-payment-list-item', function() {
                $('.js-activate-payment-list-item').removeClass('active');
                if ($(this).hasClass('active')) {
                    $('.js-payment-next').removeClass('active');
                    $('.js-edit-payment-existing-button').removeClass('active');
                    widget._chosenPayment = {
                        stripe_customer_id: null,
                        payment_code: null,
                        expiration_date: null
                    };
                    widget._enableOrDisableActivateButton();
                } else {
                    $(this).addClass('active');
                    $('.js-payment-next').addClass('active');
                    $('.js-edit-payment-existing-button').addClass('active');
                    widget._chosenPayment = {
                        stripe_customer_id: $(this).data('stripecustomerid'),
                        payment_code: $(this).data('paymentcode'),
                        expiration_date: $(this).data('expirationdate')
                    };
                    widget._enableOrDisableActivateButton();
                }
            });

            $('.js-shipping-next').on('click', function() {
                if (widget._shippingAddressId) {
                    widget._updateSubtotalFields(widget._chosenSubscription.id, widget._shippingAddressId);
                }
            });

            //This an an activate button on a subscription
            $('.js-activate-subscription').on('click', function() {
                var subscriptionId = $(this).data('subscriptionid');
                if(!widget._setChosenSubscription(subscriptionId)){
                    return console.error("Subscription not found");
                };

                $('.subscription-list__list-container, .subscription-flow').toggleClass('hide');
                $('.page.messages').addClass('flow-active');
                widget._showAppropriateIntroAndFpBlocks(subscriptionId);
            });

            $('.js-activate-subscription-option').on('click', function() {
                var subscriptionId = $(this).data('subscriptionid');
                if(!widget._setChosenSubscription(subscriptionId)){
                    return console.error("Subscription not found");
                }
                
                $('.subscription-list__list-container, .subscription-flow').toggleClass('hide');
                $('.page.messages').addClass('flow-active');
                widget._showAppropriateIntroAndFpBlocks(subscriptionId);
            });

            //This cancels out of the subscription flow
            $('.js-cancel-activate-subscription').on('click', function() {
                $('.page.messages').removeClass('flow-active');
                if ($('.block-dashboard-subscriptions').length > 0) {
                    $('.block-dashboard-subscriptions, .block-dashboard-orders').removeClass('hide');
                    $('.dashboard-promo').toggle();
                    $('.subscription-flow').toggleClass('hide');
                    $('.subscription-list__message:not(.js-dashboard-thankyou-message)').removeClass('hide');
                } else {
                    $('.subscription-list__list-container, .subscription-flow').toggleClass('hide');
                }
                widget._resetActivateFlow();
            });

            //This is the activate button at the bottom of the flow
            $('.activate-subscription').on('click', function() {
                $('.page.messages').removeClass('flow-active');
                widget._sendActivateSubscriptionClickEvent();
                if (!widget._shippingAddressId || !widget._chosenPayment) {
                    return;
                }
                if (!$('#agreement').is(':checked')) {
                    $('.js-agreement-error-box').removeClass('hide');
                    return;
                }
                $('.js-agreement-error-box').addClass('hide');
                var paymentOption = widget._chosenPayment;
                if (widget._chosenSubscription.payment && widget._chosenSubscription.payment.id) {
                    paymentOption['id'] = widget._chosenSubscription.payment.id;
                }
                paymentOption['status'] = 'valid';
                var subscriptionObject = {
                    'subscriptionCustomer': {
                        'id': widget._chosenSubscription.id,
                        'customer_id': widget._chosenSubscription.customer_id,
                        'status': 'autorenew_on',
                        'next_order': widget._getNextOrderDate(widget._chosenSubscription),
                        'shipping_address': {
                            'id': widget._shippingAddressId
                        },
                        'subscription_plan': {
                            'id': widget._chosenSubscription.subscription_plan.id
                        },
                        'device': {},
                        'payment': paymentOption,
                        'promos': [],
                        'coupon_codes': widget._promos
                    }
                };
                $.ajax({
                    showLoader: true,
                    type: 'PUT',
                    url: window.baseUrl + 'rest/V1/vonnda/subscription/me/customer',
                    headers: {
                        "Content-Type":"application/json"
                    },
                    data: JSON.stringify(subscriptionObject)
                }).success(function(data) {
                    if ($('.subscription-list__list-container').length != 0) {
                        widget._handleActivateAutoRefills(data);
                    } else {
                        widget._handleActivateDashboard(data);
                    }
                }).error(function(err) {
                    widget._sendActivateSubscriptionFailureEvent(widget._chosenSubscription, err);
                    console.log(err);
                });
            });

            $(document.body).on('click', '.js-payment-edit-existing', function() {
                var deviceName = $(this).data('devicename');
                var subscriptionId = $(this).data('subscriptionid');
                $('.js-edit-device-name').text(deviceName);
                widget._editingPayment.subscriptionId = subscriptionId;
                widget._hideActivateSectionsForPayment();
            });

            $('.js-cancel-edit-payment').on('click', function() {
                widget._cancelEditPayment();
                var dashboardSubscriptions = document.querySelector('.block-dashboard-subscriptions');
                if (dashboardSubscriptions) {
                    $('.block-dashboard-subscriptions, .block-dashboard-orders').removeClass('hide');
                    $('.dashboard-promo').toggle();
                    $('.subscription-list__message').removeClass('hide');
                }
                widget._resetActivateFlow();
            });

            $('.js-edit-payment-existing-button').on('click', function() {
                if (!widget._chosenPayment || !widget._chosenPayment.payment_code) {
                    return;
                }
                widget._updateExistingPayment();
            });

            $('.js-promo-code').on('click', function() {
                $(this).toggleClass('toggled');
                $(this).next('.promo-holder').toggleClass('hide');
            });
            $('.js-add-promo').on('click', function() {
                widget._promos = [];
                if ($('#promo-input').val()) {
                    widget._promos.push($('#promo-input').val());
                }
                $('.js-promo-container').html($('<p>').text($('#promo-input').val()).html());
                widget._updateSubtotalFields(widget._chosenSubscription.id, widget._shippingAddressId, widget._promos[0]);
                $('.promo-msg').removeClass('hide');
            });
            $('.js-remove-promo').on('click', function() {
                widget._promos = [];
                $('.js-promo-container').html('');
                $('#promo-input').val('');
                widget._updateSubtotalFields(widget._chosenSubscription.id, widget._shippingAddressId);
                $('.promo-msg').removeClass('hide');
            });

            $('#agreement').on('click', function() {
                $('.js-agreement-error-box').addClass('hide');
                widget._enableOrDisableActivateButton();
            });

            //Change Country Select
            $(document).on('change','#flow-country',function() {
                if (!widget._savedNewActivateShippingState) {
                    widget._updateStateSelectOnCountryChange('#flow-country', '.subscription-flow-new-address #state');
                }
            });
            $(document).on('change','#flow-billing-country',function() {
                if (!widget._savedNewActivateBillingState) {
                    widget._updateStateSelectOnCountryChange('#flow-billing-country', '.subscription-flow-new-billing-address #state');
                }
            });

            // New Card
            $('.subscription-flow-new-card-add').on('click', function() {
                $('.js-subscription-flow-new-card-add-error-messages').remove();
                //Editing existing payment
                if (widget._editingPayment.subscriptionId) {
                    if ($(this).hasClass('active')) {
                        $('.js-edit-payment-existing-button').show();
                    } else {
                        $('.js-payment-next').hide();
                        $('.js-edit-payment-existing-button').hide();
                    }
                } else {
                    $('.js-payment-next').toggle();
                }

                $(this).toggleClass('active');
                $(this).next('.subscription-flow-new-card-add-content').toggle();
            });
            $(document.body).on('keydown blur', '.subscription-flow-new-address .subscription-list__shipping-address-form input', this.shippingFormValidation);
            $(document.body).on('change', '.subscription-flow-new-address .subscription-list__shipping-address-form select', this.shippingFormValidation);
            $(document.body).on('keydown blur', '.subscription-flow-new-card-add-content .subscription-list__shipping-address-form input', $.proxy(this.billingFormValidation, this));
            $(document.body).on('change', '.subscription-flow-new-card-add-content .subscription-list__shipping-address-form select', $.proxy(this.billingFormValidation, this));

            $(document).on('changeCCField', function(e) {
                if (e.detail.elementType === 'cardNumber') {
                    widget._ccFieldsCompleted.cardNumber = e.detail.complete;
                } else if (e.detail.elementType === 'cardExpiry') {
                    widget._ccFieldsCompleted.cardExpiry = e.detail.complete;
                } else if (e.detail.elementType === 'cardCvc') {
                    widget._ccFieldsCompleted.cardCvc = e.detail.complete;
                }
                widget._updatePaymentSaveButtonStatus();
            });
            $(document).on('cancelAddAddressToExistingSubscription', function() {
                widget._clearForm('.js-existing-subscription-new-address-form', ['shipping-country_id']);
            });
            $(document).on('changeEditAddressExistingSubStateSelect', function() {
                widget._savedNewShippingExistingSubState = null;
            });
        },

        shippingFormValidation: function() {
            // var allRequiredFieldsHaveInput = widget._checkRequiredFields(form);
            if ($('.subscription-flow-new-address .subscription-list__shipping-address-form').find('.js-address-field').val() == '') {
                $('.js-flow-add-shipping-address').removeClass('active').prop('disabled', true);
            } else {
                $('.js-flow-add-shipping-address').addClass('active').prop('disabled', false);
            }
        },

        billingFormValidation: function() {
            // var allRequiredFieldsHaveInput = widget._checkRequiredFields(form);
            this._addressComplete = $('.subscription-flow-new-card-add-content .subscription-list__shipping-address-form').find('.js-address-field').val() != '';
            this._updatePaymentSaveButtonStatus();
        },

        //Utility
        _clearForm: function(query, selectIgnoreIds = []) {
            var form = $(query);
            form.find('input').each(function() {
                $(this).val('');
                $(this).parent().removeClass('fl-label-state').addClass('fl-placeholder-state');
            });
            form.find('checkbox').each(function() {
                $(this).prop('checked', false);
            });
            form.find('select').each(function() {
                if (!_.includes(selectIgnoreIds, $(this).attr('id'))) {
                    $(this).val('');
                }
            });
        },

        _resetActivateFlow: function() {
            this._resetWidgetVariables();
            this._resetActivateTabs();
            this._resetActivateShippingSection();
            this._resetActivatePaymentSection();
            this._resetActivateOrderSection();
        },
        _resetWidgetVariables: function() {
            this._ccFieldsCompleted = {
                cardNumber: false,
                cardExpiry: false,
                cardCvc: false
            };
            this._addressComplete = false;
            this._shippingAddressId = null;
            this._chosenPayment = null;
            this._chosenSubscription = null;
            this._promos = [];
        },
        _resetActivateTabs: function() {
            $('.js-subscription-flow-shipping').addClass('active');
            $('.js-subscription-flow-payment, .js-subscription-flow-order').removeClass('active');
            $('.js-subscription-flow-shipping, .js-subscription-flow-payment, .js-subscription-flow-order').removeClass('done');
            $('.checkout-next-step').removeClass('active').attr('disable', true);
            $('.js-subscription-flow-shipping-preview').addClass('hide').html('');
            $('.js-subscription-flow-payment-preview').addClass('hide').html('');
            $('.subscription-flow-fine-print').addClass('hide');
        },
        _resetActivateShippingSection: function() {
            $('.subscription-flow-address-add, .js-activate-shipping-address-list-item').removeClass('active');
            $('.js-shipping-next').css('display', '');
            $('.subscription-flow-new-address').hide();
            this._clearForm('.js-subscription-flow-add-address-form', ['flow-country']);
        },
        _resetActivatePaymentSection: function() {
            stripe.initStripeElements();
            stripe.clearCardErrors();
            $('.js-subscription-flow-new-card-add-error-messages').remove();
            $('.subscription-flow-new-card-add, .js-activate-payment-list-item').removeClass('active');
            $('.js-sameasshipping').addClass('active');
            $('.js-sameasshipping input[type="checkbox"]').prop('checked', true);
            $('.js-edit-payment-header').hide();
            $('.js-edit-device-name').hide();
            $('.subscription-flow-new-card-add-content').hide();
            $('.subscription-flow-new-billing-address').hide();
            $('.js-edit-payment-existing-button-container').hide();
            $('.js-activate-payment-list').show();
            $('.js-payment-next').css('display', '');
            this._clearForm('.js-subscription-flow-new-billing-address-form', ['flow-billing-country']);
        },
        _resetActivateOrderSection: function() {
            this._resetActivatePromoSection();
            $('#agreement').prop('checked', false);
            $('.activate-subscription').prop('disabled', true);
        },
        _resetActivatePromoSection: function() {
            $('#promo-input').removeClass('mage-error').val('');
            $('.js-promo-code').removeClass('toggled');
            $('.js-add-promo').removeClass('hide');
            $('.js-remove-promo').addClass('hide');
            $('.js-promo-container').html('');
            $('.promo-holder').addClass('hide');
            $('.promo-msg').addClass('hide').html('Your coupon was successfully removed.');
        },

        _showThankYouMessageOnSubscription: function(updatedSubscription) {
            var oldStatus = this._chosenSubscription.status;
            var oldNextOrderDateInPast = new Date(this._chosenSubscription.next_order) <= new Date();
            var newStatus = updatedSubscription.status;
            $('.subscription-list__message[data-subscriptionid="' + updatedSubscription.id + '"]').hide();
            var thankyouMessage = $('.subscription-list__thank-you-message[data-subscriptionid="' + updatedSubscription.id + '"]').removeClass('hide').show();
            if ((oldStatus === 'processing_error'|| oldStatus === 'payment_invalid' || oldStatus === 'payment_expired') && newStatus === 'autorenew_on') {
                thankyouMessage.text('Thanks for the update');
            }
            if (oldStatus === 'activate_eligible' && newStatus === 'autorenew_on') {
                thankyouMessage.text("You're all set. You'll get another 6 months of filters on us.");
            }
            if ((oldStatus === 'autorenew_off' || oldStatus === 'legacy_no_payment' || oldStatus === 'new_no_payment') && newStatus === 'autorenew_on') {
                if (oldNextOrderDateInPast) {
                    thankyouMessage.text('Auto-refills activated. Your filters will ship in a few days.');
                } else {
                    thankyouMessage.text("You're all set. Your filters will ship a few days after your plan renews.");
                }
            }
        },

        _setChosenSubscription: function(subscriptionId) {
            if(Array.isArray(window.allSubscriptions) && !window.allSubscriptions.length){
                return false;
            }
            if(window.allSubscriptions && window.allSubscriptions[subscriptionId]){
                this._chosenSubscription = $.extend({}, window.allSubscriptions[subscriptionId]);
                return true;
            }

            return false;
        },

        //Billing
        _overrideStripeSave: function() {
            var widget = this;
            $(document.body).on('click', '.js-stripe-payments-save-card', function(event) {
                event.preventDefault();
                if ($('.subscription-flow-new-billing-address form').validation('isValid')) {
                    var sameAsShipping = $('.js-sameasshipping input[type="checkbox"]').is(':checked') && !widget._editingPayment.subscriptionId;
                    var shippingFields = window.addresses[widget._shippingAddressId];
                    var street = [(sameAsShipping ? shippingFields['streetOne'] : $('#flow-billing-streetOne').val())];
                    var phoneValid = true;
                    if (sameAsShipping) {
                        if (shippingFields['streetTwo'] != '') {
                            street.push(shippingFields['streetTwo']);
                        }
                    } else {
                        if ($('#flow-billing-streetTwo').val() != '') {
                            street.push($('#flow-billing-streetTwo').val());
                        }
                        if ($('input#flow-billing-telephone').val().length < 14) {
                            phoneValid = false;
                        }
                    }
                    if (phoneValid) {
                        stripe.quote = {
                            guestEmail: $('#customer-email').val(),
                            billingAddress: function() {
                                return {
                                    firstname: sameAsShipping ? shippingFields['firstname'] : $('#flow-billing-firstname').val(),
                                    lastname: sameAsShipping ? shippingFields['lastname'] : $('#flow-billing-lastname').val(),
                                    street: street,
                                    city: sameAsShipping ? shippingFields['city'] : $('#flow-billing-city').val(),
                                    region: sameAsShipping ? shippingFields['state'] : $('.subscription-flow-new-billing-address #state').find('option:selected').text(),
                                    postcode: sameAsShipping ? shippingFields['postcode'] : $('#flow-billing-postcode').val(),
                                    countryId: sameAsShipping ? shippingFields['country'] : $('#flow-billing-country').find('option:selected').val(),
                                    telephone: sameAsShipping ? shippingFields['telephone'] : $('#flow-billing-telephone').val()
                                }
                            }
                        };
                        return stripe.saveCard(this, function() {
                            $.ajax({
                                type: 'POST',
                                showLoader: true,
                                url: window.BASE_URL + 'subscription/customer/addcard',
                                data: {
                                    form_key: $('.js-add-card-formkey').val(),
                                    payment: {cc_stripejs_token: stripe.token}
                                }
                            }).success(function(data) {
                                if (!data || data.status == 'error' || !data.payment_code || !data.expiration_date || !data.card_string) {
                                    var message = (data.status == 'error' && data.message ? data.message : 'There was an error adding your card.');
                                    $('.js-subscription-flow-new-card-add-error-messages').remove();
                                    var errorMessage = "<div class='messages js-subscription-flow-new-card-add-error-messages'><div class='message-error message error subscription-flow-new-card-add-error-message'><strong>Error:</strong>" + message + "</div></div>";
                                    $(errorMessage).insertAfter('.subscription-flow-new-card-add');
                                    setTimeout(function() {
                                        $('.subscription-flow-new-card-add').get(0).scrollIntoView({behavior: 'smooth', block: 'start', inline: 'start'});
                                    }, 100);
                                    console.error(data);
                                } else {
                                    stripe.initStripeElements();
                                    stripe.clearCardErrors();
                                    $('.js-subscription-flow-new-card-add-error-messages').remove();
                                    widget._clearForm('.js-subscription-flow-new-billing-address-form', ['flow-billing-country']);
                                    widget._ccFieldsCompleted = {
                                        cardNumber: false,
                                        cardExpiry: false,
                                        cardCvc: false
                                    };
                                    //This is for the payment billing address
                                    widget._addressComplete = false;
                                    $('.js-stripe-payments-save-card').removeClass('active').prop('disabled', true);
                                    $('.js-activate-payment-list-item').removeClass('active');
                                    var listItem = $('<div>')
                                        .addClass('subscription-list__billing-address-edit-list-item')
                                        .addClass('js-activate-payment-list-item')
                                        .data('paymentcode', data.payment_code)
                                        .data('expirationdate', data.expiration_date)
                                        .data('stripecustomerid', data.stripe_customer_id)
                                        .append($('<p>').append($('<span>').addClass('cc-last4').html(data.card_string)).append($('<span>').addClass('cc-exp').html('exp. ' + data.expiration_date)))
                                        .addClass('active');
                                    $('.js-activate-payment-list').prepend(listItem);
                                    $('.js-edit-payment-existing-button').addClass('active');
                                    widget._chosenPayment = {payment_code: data.payment_code, expiration_date: data.expiration_date, stripe_customer_id: data.stripe_customer_id};
                                    $('.subscription-flow-new-card-add-content').toggle();
                                    $('.js-payment-next').addClass('active').toggle();
                                    if (widget._editingPayment.subscriptionId) {
                                        $('.js-payment-next').hide();
                                        $('.js-edit-payment-existing-button').toggle();
                                    }
                                    $('.subscription-flow-new-card-add').removeClass('active');
                                }
                            }).error(function(err) {
                                console.log('error', err);
                            });
                        });

                    } else {
                        $('input#flow-billing-telephone').addClass('mage-error');
                        $('<div class="mage-error" generated="true">Please enter valid phone number.</div>').insertAfter('input#flow-billing-telephone');
                    }
                }
            });
        },

        _updatePaymentSaveButtonStatus: function() {
            if ((this._editingPayment.subscriptionId ? this._addressComplete : ($('#sameasshipping').is(':checked') || (!$('#sameasshipping').is(':checked') && this._addressComplete)))
                && this._ccFieldsCompleted.cardNumber && this._ccFieldsCompleted.cardExpiry && this._ccFieldsCompleted.cardCvc) {
                $('.js-stripe-payments-save-card').addClass('active').prop('disabled', false);
            } else {
                $('.js-stripe-payments-save-card').removeClass('active').prop('disabled', true);
            }
        },

        _showAppropriateIntroAndFpBlocks: function(subscriptionId) {
            $('.subscription-flow-intro').addClass('hide');
            $('.subscription-flow-fine-print').addClass('hide');
            $.ajax({
                showLoader: true,
                type: 'GET',
                url: window.baseUrl + 'subscription/customer/getactivatesections?subscriptionId=' + subscriptionId
            }).success(function(data) {
                if (data.Status === 'success') {
                    $('.subscription-flow-intro').html(data.introBlockContent).removeClass('hide');
                    var fpBlock = $('.subscription-flow-fine-print');
                    if (fpBlock) {
                        fpBlock.html(data.finePrintContent).removeClass('hide');
                        modal({
                            'type': 'popup',
                            'modalClass': 'agreements-modal',
                            title: ' ',
                            'responsive': true,
                            'innerScroll': true,
                            'buttons': []
                        }, $('#cancel-popup-content'));
                        $('.cancel-anytime-link').on('click', function(e) {
                            e.preventDefault();
                            $('#cancel-popup-content').modal('openModal');
                        });
                    }
                }
            }).error(function(err) {
                console.log(err);
            });
        },

        _handleActivateAutoRefills: function(data) {
            var updatedSubscription = null;
            for (var i = 0; i < data.length; i++) {
                if (data[i]['id'] == this._chosenSubscription.id) {
                    updatedSubscription =  $.extend({},data[i]);
                }
            }
            if (this._newRegistration && this._newRegistration == updatedSubscription.id) {
                var messageContainer = document.querySelector('.js-registered-banner');
                messageContainer.classList.add('hide');
                mage.cookies.clear('device_registration_success');
                this._newRegistration = null;
            }
            this._updateCardInfo(updatedSubscription);
            this._sendActivateSubscriptionSuccessEvent(updatedSubscription, data);
            //scroll to subscription
            $(document).trigger('changeAddressText', [updatedSubscription.id, updatedSubscription.shipping_address.id, updatedSubscription.shipping_address.street[0]]);
            this._updateRenewalDateText(updatedSubscription.renewal_date, updatedSubscription.id);
            $('.subscription-list__subscription-container[data-id="' + updatedSubscription.id + '"]').find('.js-next-shipment-date').text(updatedSubscription.week_of_string);
            $('.subscription-list__subscription-container[data-id="' + updatedSubscription.id + '"]').find('.js-expiration-date-container').addClass('hide');
            this._showThankYouMessageOnSubscription(updatedSubscription);
            this._toggleOptionButtons(updatedSubscription.id);
            this._hideActivateButton(updatedSubscription.id);
            this._resetActivateFlow();
            $('.subscription-list__list-container, .subscription-flow').toggleClass('hide');
            this._chosenSubscription = null;
        },

        _handleActivateDashboard: function(data) {
            var updatedSubscription = null;
            for (var i = 0; i < data.length; i++) {
                if (data[i]['id'] == this._chosenSubscription.id) {
                    updatedSubscription =  $.extend({},data[i]);
                }
            }
            this._hideActivateButton(updatedSubscription.id);
            this._sendActivateSubscriptionSuccessEvent(updatedSubscription, data);
            this._handleDashboardMessagesOnActivateSuccess(data, updatedSubscription);
            $('.subscription-flow').addClass('hide');
            $('.block-dashboard-orders, .block-dashboard-subscriptions').removeClass('hide');
            $('.dashboard-promo').toggle();
            this._resetActivateFlow();
            this._chosenSubscription = null;
        },

        _handleDashboardMessagesOnActivateSuccess: function(data, updatedSubscription) {
            var oldStatus = this._chosenSubscription.status;
            var oldNextOrderDate = moment(this._chosenSubscription.next_order);
            var oldNextOrderDateInPast = oldNextOrderDate < moment();
            var thankyouMessage = $('.js-dashboard-thankyou-message').removeClass('hide').show();
            var oldStatusWasError = oldStatus === 'processing_error' || oldStatus === 'payment_invalid' || oldStatus === 'payment_expired';
            if (oldStatusWasError && updatedSubscription.status === 'autorenew_on') {
                thankyouMessage.text('Thanks for the update');
                var otherErrorStatus = _.some(data, function(subscription) {
                    if (subscription.status === 'processing_error' || subscription.status === 'payment_invalid' || subscription.status === 'payment_expired') {
                        $('.js-dashboard-card-processing-error-msg').find('.msg-button').prop('href', window.BASE_URL + '/subscription/customer/autorefill?subscription=' + subscription.id);
                        return true;
                    }
                    return false;
                });
                if (!otherErrorStatus) {
                    $('.js-dashboard-card-processing-error-msg').hide();
                }
            }
            if (oldStatus === 'activate_eligible' && updatedSubscription.status === 'autorenew_on') {
                thankyouMessage.text('You\'re all set. You\'ll get another 6 months of filters on us.');
                var otherActivateEligible = _.some(data, function(subscription) {
                    if (subscription.status === 'activate_eligible') {
                        $('.js-dashboard-activate-eligible-msg').find('.msg-button').prop('href', window.BASE_URL + '/subscription/customer/autorefill?subscription=' + subscription.id);
                        return true;
                    }
                    return false;
                });
                if (!otherActivateEligible) {
                    $('.js-dashboard-activate-eligible-msg').hide();
                }
            }
            var oldStatusApp = oldStatus === 'autorenew_off' || oldStatus === 'legacy_no_payment' || oldStatus === 'new_no_payment';
            if (oldStatusApp && updatedSubscription.status === 'autorenew_on' && oldNextOrderDateInPast) {
                thankyouMessage.text('Auto-refills activated. Your filters will ship in a few days.');
                var otherExpired = _.some(data, function(subscription) {
                    if (subscription.status === 'autorenew_off' && moment(subscription.next_order) < moment()) {
                        $('.js-dashboard-expired-msg').find('.msg-button').prop('href', window.BASE_URL + '/subscription/customer/autorefill?subscription=' + subscription.id);
                        return true;
                    }
                    return false;
                });
                if (!otherExpired) {
                    $('.js-dashboard-expired-msg').hide();
                }
            }
            if (oldStatusApp && updatedSubscription.status === 'autorenew_on' && !oldNextOrderDateInPast) {
                thankyouMessage.text('You\'re all set. Your filters will ship a few days after your plan renews.');
                var otherAutorenewOff = _.some(data, function(subscription) {
                    if (subscription.status === 'autorenew_off' && moment(subscription.next_order) < moment()) {
                        $('.js-dashboard-will-expire-soon-msg').find('.msg-button').prop('href', window.BASE_URL + '/subscription/customer/autorefill?subscription=' + subscription.id);
                        return true;
                    }
                    return false;
                });
                if (!otherAutorenewOff) {
                    $('.js-dashboard-will-expire-soon-msg').hide();
                }
            }
        },

        _getNextOrderDate: function(subscriptionObject) {
            if (moment(subscriptionObject.next_order) > moment()) {
                return subscriptionObject.next_order;
            } else {
                return moment().endOf('day').format('YYYY-MM-DD H:mm:ss');
            }
        },

        _hideActivateButton: function(subscriptionId) {
            $('.js-activate-subscription[data-subscriptionid="' + subscriptionId + '"]').addClass('hide');
        },

        _updateCardInfo: function(subscriptionObject) {
            $.ajax({
                type: 'GET',
                url: window.baseUrl + 'rest/V1/cryozonic/stripepayments/me/card-info'
            }).success(function(data) {
                var card = false;
                for (var i = 0; i < data.length; i++) {
                    if (data[i]['id'] === subscriptionObject.payment.payment_code) {
                        card = data[i];
                    }
                }
                var cardBox = $('.js-subscription-list-payment-info-container[data-subscriptionid="' + subscriptionObject.id + '"]').removeClass('hide').find('.js-subscription-list-payment-info');
                cardBox.html(card.brand + ' ' + card.last4 + ' ' + '<span class="cc-exp">'+card.exp_month + '/' + card.exp_year + '</span>' + cardBox.find('button')[0].outerHTML);
            }).error(function(err) {
                console.log(err);
            });
        },

        _updateRenewalDateText: function(date, subscriptionId) {
            var subContainer = $('.subscription-list__subscription-container[data-id="' + subscriptionId + '"]');
            subContainer.find('.js-expiration-date').addClass('hide');
            subContainer.find('.js-renewal-date').text(date);
            subContainer.find('.js-renewal-date-container').removeClass('hide');
        },

        _enableOrDisableActivateButton: function() {
            var widget = this;
            $('.activate-subscription').attr('disabled', !(!!(widget._chosenPayment && widget._chosenPayment.payment_code) && !!(widget._shippingAddressId) && $('#agreement').is(':checked')));
        },

        _togglePreview: function(element) {
            if (element.hasClass('js-shipping-next')) {
                if (!this._shippingAddressId) return;
                var address = window.addresses[this._shippingAddressId];
                $('.js-subscription-flow-shipping-preview').removeClass('hide')
                    .append($('<p>').html(address.firstname + " " + address.lastname))
                    .append($('<p>').html(address.streetOne))
                    .append($('<p>').html(address.streetTwo))
                    .append($('<p>').html(address.city + ", " + (!address.state && address.regioncode ? address.regioncode : address.state) + " " + address.postcode))
                    .append($('<p>').html(address.telephone));
                $('.js-subscription-flow-payment-preview').addClass('hide').html('');
            } else if (element.hasClass('js-shipping-edit')) {
                $('.js-subscription-flow-shipping-preview').addClass('hide').html('');
            } else if (element.hasClass('js-payment-next')) {
                if (!this._chosenPayment) return;
                $('.js-subscription-flow-payment-preview').removeClass('hide')
                    .append($('<p>').html($('.js-activate-payment-list-item.active').find('.cc-last4').text() + " " + $('.js-activate-payment-list-item.active').find('.cc-exp').text()));
            } else if (element.hasClass('js-payment-edit')) {
                $('.js-subscription-flow-payment-preview').addClass('hide').html('');
            }
        },

        _toggleOptionButtons: function(subscriptionId) {
            var subContainer = $('.subscription-list__subscription-container[data-id="' + subscriptionId + '"]');
            subContainer.find('.js-activate-subscription-option').addClass('hide');
            subContainer.find('.js-open-cancel').removeClass('hide');
        },

        //TODO - refactor this
        _hideActivateSectionsForPayment: function() {
            $('.activate-no-card-shipping').removeClass('hide');
            $('.subscription-flow-intro').hide();
            $('.js-subscription-flow-shipping').hide();
            $('.js-subscription-flow-order').hide();
            $('.js-sameasshipping').hide();
            $('.js-payment-title').hide();
            $('.js-payment-header').hide();
            $('.js-payment-next').hide();
            $('.subscription-list__list-container').hide();
            $('.js-subscription-flow-payment').show();
            $('.js-payment-group-content').show();
            $('.subscription-flow-new-billing-address').show();
            $('.js-edit-payment-header').show();
            $('.js-edit-device-name').show();
            $('.js-edit-payment-existing-button-container').show();
            $('.js-sameasdefaultbilling').show();
        },

        //Reverse all of the damage we did above
        _cancelEditPayment:function() {
            this._editingPayment.subscriptionId = null;
            $('.activate-no-card-shipping').addClass('hide');
            $('.subscription-flow-intro').show();
            $('.js-subscription-flow-device').show();
            $('.js-subscription-flow-shipping').show();
            $('.js-subscription-flow-order').show();
            $('.js-subscription-flow-payment').show();
            $('.js-sameasshipping').show();
            $('.js-payment-title').show();
            $('.js-payment-header').show();
            $('.subscription-list__list-container').show();
            $('.js-edit-payment-header').show();
            $('.js-payment-next').css('display', '');
            $('.js-payment-group-content').css('display', '');
            $('.subscription-flow-new-billing-address').hide();
            $('.js-edit-device-name').hide();
            $('.js-edit-payment-existing-button-container').hide();
            $('.js-sameasdefaultbilling').hide();
        },

        _updateExistingPayment: function() {
            var widget = this;
            var subscription = null;
            var paymentOption = this._chosenPayment;

            if(Array.isArray(window.allSubscriptions) && !window.allSubscriptions.length){
                return console.error("Cannot find subscription");
            }

            if(window.allSubscriptions && window.allSubscriptions[this._editingPayment.subscriptionId]){
                subscription = $.extend({}, window.allSubscriptions[this._editingPayment.subscriptionId]);
            } else {
                return console.error("Cannot find subscription");
            }

            this._sendUpdatePaymentClickEvent(subscription);
            if (subscription.payment && subscription.payment.id) {
                paymentOption['id'] = subscription.payment.id;
            }
            paymentOption['status'] = 'valid';
            var newSubscriptionObject = {
                'subscriptionCustomer': {
                    'id': subscription.id,
                    'customer_id': subscription.customer_id,
                    'status': 'autorenew_on',
                    'next_order': this._getNextOrderDate(subscription),
                    'subscription_plan': {
                        'id': subscription.subscription_plan.id
                    },
                    'device': {},
                    'payment': paymentOption,
                    'promos': [],
                    'coupon_codes': []
                }
            };
            if (subscription.shipping_address) {
                newSubscriptionObject['subscriptionCustomer']['shipping_address'] = {
                    'id': subscription.shipping_address.id
                };
            }
            $.ajax({
                showLoader: true,
                type: 'PUT',
                url: window.baseUrl + 'rest/V1/vonnda/subscription/me/customer',
                headers: {
                    "Content-Type":"application/json"
                },
                data: JSON.stringify(newSubscriptionObject)
            }).success(function(data) {
                widget._handleUpdatePaymentSuccess(data, newSubscriptionObject.subscriptionCustomer.id);
            }).error(function(err) {
                //TODO - this needs error messaging
                widget._sendUpdatePaymentFailureEvent(widget._chosenSubscription);
                console.log(err);
            });
        },

        _handleUpdatePaymentSuccess: function(data, subscriptionId) {
            this._cancelEditPayment();
            var updatedSubscription = _.find(data, function(elem) {
                return elem['id'] === subscriptionId;
            });
            this._sendUpdatePaymentSuccessEvent(updatedSubscription);
            var oldSubscription = window.allSubscriptions[subscriptionId];
            
            var newSubscription = _.find(data, function(subscription) {
                return subscription.id == subscriptionId;
            });
            if ($('.block-dashboard-subscriptions').length > 0) {
                $('.block-dashboard-subscriptions, .block-dashboard-orders').removeClass('hide');
                $('.dashboard-promo').toggle();
                $('.subscription-list__message:not(.js-dashboard-thankyou-message)').removeClass('hide');
                $('.js-payment-edit-existing[data-subscriptionid="' + subscriptionId + '"]').addClass('hide');
                if ((oldSubscription.status === 'processing_error' || oldSubscription.status === 'payment_invalid' || oldSubscription.status === 'payment_expired') && newSubscription.status === 'autorenew_on') {
                    $('.js-dashboard-thankyou-message').removeClass('hide').show().text('Thanks for the update');
                    var otherErrorStatus = _.some(data, function(subscription) {
                        if (subscription.status === 'processing_error' || subscription.status === 'payment_invalid' || subscription.status === 'payment_expired') {
                            $('.js-dashboard-card-processing-error-msg').find('.msg-button').attr('href', window.BASE_URL + '/subscription/customer/autorefill?subscription=' + subscription.id);
                            return true;
                        }
                        return false;
                    });
                    if (!otherErrorStatus) {
                        $('.js-dashboard-card-processing-error-msg').hide();
                    }
                    var somePaymentWillExpire = _.some(data, function(subscription) {
                        var dateArr = subscriptionObject.payment.expiration_date.split("/");
                        var cardExpirationDate = new Date(dateArr[1], (dateArr[0])-1, 28);
                        if ((new Date().setMonth(new Date().getMonth() + 1)) >= cardExpirationDate) {
                            $('.js-dashboard-card-expire-soon-msg').find('.msg-button').attr('href', window.BASE_URL + '/subscription/customer/autorefill?subscription=' + subscription.id);
                            return true;
                        }
                        return false;
                    });
                    if (!somePaymentWillExpire) {
                        $('.js-dashboard-card-expire-soon-msg').hide();
                    }
                }
            } else {
                this._updateRenewalDateText(updatedSubscription.renewal_date, updatedSubscription.id);
                $('.subscription-list__subscription-container[data-id="' + updatedSubscription.id + '"]').find('.js-next-shipment-date').text(updatedSubscription.week_of_string);
                $('.subscription-list__subscription-container[data-id="' + updatedSubscription.id + '"]').find('.js-expiration-date-container').addClass('hide');
                this._hideActivateButton(updatedSubscription.id);
                this._updateCardInfo(updatedSubscription);
                this._toggleOptionButtons(updatedSubscription.id);
                $('.subscription-list__message[data-subscriptionid="' + subscriptionId + '"]').hide();
                if (!(oldSubscription.status === 'processing_error' || oldSubscription.status === 'payment_invalid' || oldSubscription.status === 'payment_expired') && newSubscription.status === 'autorenew_on') {
                    $('.subscription-list__thank-you-message[data-subscriptionid="' + subscriptionId + '"]').removeClass('hide').show().text('Thanks for the update.');
                }
                $('.js-payment-edit-existing[data-subscriptionid="' + subscriptionId + '"]').removeClass('hide');
                $('.js-payment-edit-existing-update[data-subscriptionid="' + subscriptionId + '"]').addClass('hide');
                $('.subscription-list__subscription-container[data-id="' + subscriptionId + '"]').find('.js-subscription-list-payment-error-message').addClass('hide');
            }
            this._resetActivateFlow();
        },

        _updateSubtotalFields: function(subscriptionId, shippingAddressId, promo = null) {
            var promoMsg = $('.promo-msg').hide();
            var promoInput = $('#promo-input');
            var discountRow = $('tr.discount-row');
            var postData = {
                subscriptionCustomerEstimateQuery:{
                    subscription_id:subscriptionId,
                    shipping_address_id:shippingAddressId,
                    coupon_codes:[]
                }
            };
            if (promo) {
                var savePromoBtn = $('.js-add-promo');
                var removePromoBtn = $('.js-remove-promo');
                promoMsg.html('Your coupon was successfully applied.');
                var classToAdd = 'mage-success';
                var classToRemove = 'mage-error';
                var promoValHolder = $('.js-order-discount');
                postData.subscriptionCustomerEstimateQuery.coupon_codes.push(promo);
            } else {
                var savePromoBtn = $('.js-remove-promo');
                var removePromoBtn = $('.js-add-promo');
                promoMsg.html('Your coupon was successfully removed.');
                var classToAdd = 'mage-success';
                var classToRemove = 'mage-success';
            }
            $.ajax({
                type: 'POST',
                showLoader: true,
                url: window.baseUrl + 'rest/V1/vonnda/subscription/me/customer/estimate',
                headers: {'Content-Type':'application/json'},
                data: JSON.stringify(postData)
            }).success(function(data) {
                if (data.promo_code === '') {
                    promoMsg.removeClass('mage-success').addClass('mage-error').html('Invalid code. Please verify and try again.').show();
                    promoInput.addClass('mage-error');
                    discountRow.addClass('hide')
                } else {
                    promoMsg.removeClass(classToRemove).addClass(classToAdd).show();
                    promoInput.removeClass('mage-error');
                    savePromoBtn.addClass('hide');
                    removePromoBtn.removeClass('hide');
                    if (typeof promoValHolder != 'undefined') {
                        promoValHolder.html('-$'+ (data.subtotal + data.shipping + data.tax - data.order_total).toFixed(2));
                        discountRow.removeClass('hide');
                    } else {
                        discountRow.addClass('hide');
                    }
                }
                $('.js-order-summary-subtotal').text(typeof data.subtotal === 'number' ? '$' + data.subtotal.toFixed(2) : '');
                $('.js-order-summary-shipping').text(typeof data.shipping === 'number' ? '$' + data.shipping.toFixed(2) : '');
                $('.js-order-summary-tax').text(typeof data.tax === 'number' ? '$' + data.tax.toFixed(2) : '');
                $('.js-order-summary-total').text(typeof data.order_total === 'number' ? '$' + data.order_total.toFixed(2) : '');
                //TODO - remove spinner on order summary fields
            }).error(function(err) {
                console.log(err);
            });
        },

        //Country Selects
        _updateStateSelectOnCountryChange: function(countrySelector, stateSelector, countryIdOverride = null, savedStateField = null) {
            var countryId = (countryIdOverride ? countryIdOverride : $(countrySelector).find('option:selected').val());
            $.ajax({
                showLoader: true,
                url: window.BASE_URL + '/rest/V1/directory/countries/' + countryId,
                type: "GET"
            }).success(function (data) {
                var select = $(stateSelector).html('');
                select.append($('option').val('').text('State'));
                if (data.available_regions) {
                    for (var i = 0; i < data.available_regions.length; i++) {
                        var option = $('option')
                            .val(data.available_regions[i]['id'])
                            .text(data.available_regions[i]['name']);
                        select.appendChild(option);
                    }
                }
                if (savedStateField) {
                    select.val(select.find('option:contains('+savedStateField+')').val());
                } else {
                    select.selectedIndex = 0;
                }
            }).error(function (err) {
                console.log(err);
            });
        },

        //Google AutoComplete
        _initAllGoogleAutoComplete: function() {
            //Assign new address to subscription form
            this._newShippingExistingSubAutocomplete;
            this._savedNewShippingExistingSubState = null;
            this._newShippingExistingSubFormQueryMap = {
                street: '#shipping-streetOne',
                locality: '#shipping-city',
                administrative_area_level_1: '.js-existing-subscription-new-address-form #state',
                country: '#shipping-country_id',
                postal_code: '#shipping-postcode'
            };
            this._initGoogleAutoComplete('shipping-streetOne', this._newShippingExistingSubAutocomplete, this._newShippingExistingSubFormQueryMap, this._savedNewShippingExistingSubState);
            //Activate flow new shipping form
            this._newActivateShippingAutocomplete;
            this._savedNewActivateShippingState = null;
            this._newActivateShippingFormQueryMap = {
                street: '#flow-shipping-streetOne',
                locality: '#flow-shipping-city',
                administrative_area_level_1:'.js-subscription-flow-add-address-form #state',
                country:'.js-subscription-flow-add-address-form #flow-country',
                postal_code: '#flow-shipping-postcode'
            };
            this._initGoogleAutoComplete('flow-shipping-streetOne', this._newActivateShippingAutocomplete, this._newActivateShippingFormQueryMap, this._savedNewActivateShippingState);
            //Activate flow add new billing form
            this._newActivateBillingAutocomplete;
            this._savedNewActivateBillingState = null;
            this._newActivateBillingFormQueryMap = {
                street: '#flow-billing-streetOne',
                locality: '#flow-billing-city',
                administrative_area_level_1:'.js-subscription-flow-new-billing-address-form #state',
                country:'#flow-billing-country',
                postal_code: '#flow-billing-postcode'
            };
            this._initGoogleAutoComplete('flow-billing-streetOne', this._newActivateBillingAutocomplete, this._newActivateBillingFormQueryMap, this._savedNewActivateBillingState);
        },

        _initGoogleAutoComplete: function(fieldId, autoCompleteObject, queryMap, savedStateField) {
            var element = document.getElementById(fieldId);
            if (!element) return;
            autoCompleteObject = new google.maps.places.Autocomplete(element, {types: ['geocode']});
            autoCompleteObject.setComponentRestrictions({'country' : [window.storeCountryCode]});
            autoCompleteObject.setFields(['address_component']);
            autoCompleteObject.addListener('place_changed', this._fillInAddress.bind(this, autoCompleteObject, queryMap, savedStateField));
            $('#' + fieldId).on('focus', function() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        var geolocation = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };
                        var circle = new google.maps.Circle({center: geolocation, radius: position.coords.accuracy});
                        autoCompleteObject.setBounds(circle.getBounds());
                    });
                }
            });
        },

        _fillInAddress: function(autoCompleteObject, queryMap, savedStateField) {
            var place = autoCompleteObject.getPlace();
            function findByType(type) {
                return _.find(place.address_components, function(component) {
                    return _.contains(component.types, type);
                });
            }
            var streetNumber = findByType('street_number');
            var route = findByType('route');
            if (streetNumber) {
                $(queryMap['street']).val(streetNumber.long_name + ' ' + route.short_name + '.');
            } else if (route) {
                $(queryMap['street']).val(route.short_name + '.');
            }
            $(queryMap['street']).parent().addClass('fl-label-state').removeClass('fl-placeholder-state');
            var city = findByType('locality');
            if (city) {
                $(queryMap['locality']).val(city.long_name);
            }
            $(queryMap['locality']).parent().addClass('fl-label-state').removeClass('fl-placeholder-state');
            var postCode = findByType('postal_code');
            if (postCode) {
                $(queryMap['postal_code']).val(postCode.long_name);
            }
            $(queryMap['postal_code']).parent().addClass('fl-label-state').removeClass('fl-placeholder-state');
            var country = findByType('country');
            var regionCode = findByType('administrative_area_level_1');
            if ($(queryMap['country']).find('option:selected').text() === country.long_name) {
                $(queryMap['administrative_area_level_1']).val($(queryMap['administrative_area_level_1']).find('option:contains('+regionCode.long_name+')').val());
            } else {
                $(queryMap['country']).val($(queryMap['country']).find('option:contains('+country.long_name+')').val());
                this._updateStateSelectOnCountryChange(queryMap['country'], queryMap['administrative_area_level_1'], country.short_name, regionCode.long_name);
            }
        },

        //Terms and Conditions Modal
        _bindModal: function() {
            var widget = this;
            $('.js-terms-and-conditions').on('click', function(e) {
                e.preventDefault();
                widget._onClickTermsAndConditions();
            });
            $(document).on('click', $.proxy(this._onClickDocument, this));
        },

        _onClickTermsAndConditions: function() {
            $('.js-terms-and-conditions-content').modal('openModal');
        },

        _onClickDocument: function(e) {
            var popupContent = $('.js-terms-and-conditions-content');
            var popupData = popupContent.data('mageModal');
            if (!utils.isEmpty(popupData) && popupData.options.isOpen && !popupContent.is(e.target) && popupContent.has(e.target).length === 0 && $(e.target).data('role') !== this.element.data('role')) {
                popupContent.modal('closeModal');
            }
        },

        _checkRequiredFields:function(form) {
            form.find('input').each(function() {
                if ($(this).data('validate') === "{required:true}" && $(this).val() == '') {
                    return false;
                }
            });
            form.find('select').each(function() {
                if ($(this).data('validate') === "{'validate-select':true}" && $(this).hasClass('required-entry') && $(this).val() == '') {
                    return false;
                }
            });
            return true;
        },

        // Tealium Tag Integration
        _sendActivateSubscriptionClickEvent: function() {
            if (this._chosenSubscription) {
                $(document).trigger('activateSubscriptionClick', [this._chosenSubscription]);
            }
        },

        _sendActivateSubscriptionSuccessEvent: function(subscription, data) {
            $(document).trigger('activateSubscriptionSuccess', [subscription, data]);
        },

        _sendActivateSubscriptionFailureEvent: function(subscription, err) {
            $(document).trigger('activateSubscriptionFailure', [subscription, err]);
        },

        _sendUpdatePaymentClickEvent: function(subscription) {
            $(document).trigger('updatePaymentClick', [subscription]);
        },

        _sendUpdatePaymentSuccessEvent: function(subscription) {
            $(document).trigger('updatePaymentSuccess', [subscription]);
        },

        _sendUpdatePaymentFailureEvent: function(subscription) {
            $(document).trigger('updatePaymentFailure', [subscription]);
        },

    });

    return $.mage.activateRefills;
});