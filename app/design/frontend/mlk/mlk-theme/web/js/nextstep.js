define([
    'jquery',
    'underscore',
    'ko',
    'uiRegistry',
    'Magento_Checkout/js/model/quote',
    'Aheadworks_OneStepCheckout/js/model/payment-validation-invoker',
    'Aheadworks_OneStepCheckout/js/view/form/email',
    'Magento_Checkout/js/model/shipping-rates-validator',
    'Aheadworks_OneStepCheckout/js/action/update-gift-message',
    'matchMedia',
    'mage/validation'
], function(
    $,
    _,
    ko,
    registry,
    quote,
    paymentValidationInvoker,
    emailValidator,
    shippingRateValidator,
    giftMessageAction,
    matchMedia
) {
    'use strict';
    $.widget('mage.nextStep', {
        options: {},
        recipientTimeout: null,
        _create: function() {
            this._nextStep();
            this._enableShippingButtonforLoggedIn();
            this._adjustFlowForGiftOrder();
        },

        _nextStep: function () {
            var _self = this;
            var btn = _self.element;
            btn.on('click', function() {
                var parentBlock = _self.element.closest('.aw-onestep-groups_item');
                var validate = _self._validateStep(parentBlock);
                if (typeof validate === 'object' && typeof validate.then === 'function') {
                    validate.done(function() {
                        if (_self._validateBillingAddress()) {
                            _self.changeStep(parentBlock);
                        }
                    });
                } else {
                    if (validate) {
                        _self.changeStep(parentBlock);
                    }
                }
            });
        },

        switchActive: function(parentBlock) {
            if (!parentBlock.hasClass('payment-methods')) {
                parentBlock.find('.group-preview').remove();
                parentBlock.removeClass('active').addClass('done');
                $('.aw-onestep-groups_item').not(parentBlock).not('.done').first().addClass('active');
            }
        },

        changeStep: function(parentBlock) {
            var preview = '';
            if (parentBlock.hasClass('shipping')) {
                if ($('.gift-message-fieldset').length == 1) {
                    /*
                    var widget = this;
                    var giftMessage = {
                        recipient: $('.gift-message-fieldset .recipient-field input').val(),
                        message: $('.gift-message-fieldset .message-field textarea').val()
                    };
                    var giftPromise = giftMessageAction('order', giftMessage);
                    $.when(giftPromise).done(function() {
                        preview = widget.shippingChangeStep(parentBlock);
                        preview = giftMessage.recipient+'<br /><br />'+(giftMessage.message != '' ? giftMessage.message+'<br /><br />' : '')+preview;
                        widget.finishChangeStep(parentBlock, preview);
                    }).fail(function() {
                        widget.scrollTo('#checkout');
                    });
                     */
                    preview = this.shippingChangeStep(parentBlock);
                    preview = $('.gift-message-fieldset .recipient-field input').val()+'<br /><br />'+preview;
                    this.finishChangeStep(parentBlock, preview);
                } else {
                    preview = this.shippingChangeStep(parentBlock);
                    this.finishChangeStep(parentBlock, preview);
                }
            } else {
                this.switchActive(parentBlock);
                if (parentBlock.hasClass('shipping-method')) {
                    $(document).trigger('tealiumEventPaymentStep', [this._getShippingMethodAndCost(parentBlock)]);
                    if ($('.onestep-shipping-method_warning').length == 1) {
                        preview += $('.onestep-shipping-method_warning')[0].outerHTML;
                    }
                    preview += parentBlock.find('.shipping-method-card.choice .shipping-method-title input[type="radio"]:checked + .label').text()+'<br />';
                    var shippingSubtitle = parentBlock.find('.shipping-method-card.choice .shipping-method-title input[type="radio"]:checked + .label +.shipping-method-subtitle');
                    if (shippingSubtitle.length > 0) {
                        preview += shippingSubtitle.text() + '<br />';
                    }
                    preview += parentBlock.find('.shipping-method-card.choice input[type="radio"]:checked').closest('.shipping-method-card.choice').find('.shipping-method-price').text();
                    this.ccStripeListener();
                    if (parentBlock.next('.payment-methods').find('div.choice #affirm_gateway:checked').length > 0 ||
                        $('.stripe-payments-saved-card').find('input:checked').length > 0 ){
                        $('.aw-onestep-groups_item.payment-methods .checkout-next-step').removeClass('inactive');
                    }
                    this.scrollTo('#step-payment-method');
                }
                if (parentBlock.hasClass('payment-methods')) {
                    this.scrollTo('.aw-onestep-sidebar-header');
                } else {
                    this.finishChangeStep(parentBlock, preview);
                }
            }
        },

        shippingChangeStep: function(parentBlock) {
            var preview = '';
            this.switchActive(parentBlock);
            if (parentBlock.find('.shipping-address-item.selected-item').length > 0) {
                var clone = parentBlock.find('.shipping-address-item.selected-item');
                var address = this._getChosenShippingAddressExistingCustomer(clone);
                clone.find('button').remove();
                preview = clone.html();
                if(address){
                    $(document).trigger('tealiumEventShippingStep', [address]);
                }
            } else {
                var data = this.serializedInputToJson(parentBlock.find(':input'));
                preview =
                    data.firstname+' '+data.lastname+'<br />'+
                    data.street+'<br />'+
                    data.city+', '+parentBlock.find('select[name="region_id"] option:checked').text()+' '+data.postcode+'<br />'+
                    data.telephone;
                $(document).trigger('tealiumEventShippingStep', [this._addRegionToAddress(data)]);
            }
            if (parentBlock.next('.shipping-method').find('div.choice input[type="radio"]:checked').length > 0) {
                $('.aw-onestep-groups_item.shipping-method .checkout-next-step').removeClass('inactive');
            } else {
                quote.shippingMethod.subscribe(function () {
                    if(parentBlock.next('.shipping-method').find('div.choice input[type="radio"]:checked').length > 0) {
                        $('.aw-onestep-groups_item.shipping-method .checkout-next-step').removeClass('inactive');
                    }
                }, this);
            }
            this.scrollTo('#step-shipping-method');
            return preview;
        },
        finishChangeStep: function(parentBlock, preview) {
            parentBlock.find('.group-title').after('<div class="group-preview">'+preview+'</div>');
            parentBlock.find('.checkout-next-step').html('Save').removeClass('inactive');
            parentBlock.find('.checkout-edit-step-cancel').hide();
        },

        scrollTo: function(target) {
            console.log('!scroll to '+target);
            setTimeout(function() {
                $(target).get(0).scrollIntoView(
                    {
                        behavior: 'smooth',
                        block: 'start',
                        inline: 'start'
                    }
                );
            }, 100);
        },

        ccStripeListener: function () {
            var targetNodes = $(".stripe-elements-field");
            var MutationObserver    = window.MutationObserver || window.WebKitMutationObserver;
            var myObserver          = new MutationObserver (mutationHandler);
            var obsConfig = { attributes: true };

            //--- Add a target node to the observer. Can only add one node at a time.
            targetNodes.each ( function () {
                console.log('targeting your nodes');
                myObserver.observe (this, obsConfig);
            } );

            function mutationHandler(mutationRecords) {
                if ($('#stripe-payments').is(':checked') && $('.stripe-payments-saved-card').find('input:checked').length == 0) {
                    mutationRecords.forEach ( function (mutation) {
                        if ($(mutation.target).hasClass('StripeElement--complete')) {
                            var ccNext = true;
                            targetNodes.not($(mutation.target)).each(function(){
                                if(!$(this).hasClass('StripeElement--complete')) {
                                    ccNext = false;
                                }
                            });
                            if(ccNext) {
                                $('#step-payment-method').find('.checkout-next-step').removeClass('inactive');
                            } else {
                                $('#step-payment-method').find('.checkout-next-step').addClass('inactive');
                            }
                        } else {
                            $('#step-payment-method').find('.checkout-next-step').addClass('inactive');
                        }
                    } );
                }
            }
        },


        _getChosenShippingAddressExistingCustomer: function(clone){
            //TODO - Refactor
            var innerText = clone[0].innerText;
            var addressArr = innerText.split(/\r?\n/);
            var street = addressArr[3].trim();
            return _.find(window.checkoutConfig.customerData.addresses, function(address){
                return address.street[0] == street;
            });
        },

        _getShippingMethodAndCost: function(parentBlock){
            var typeObj = _.first(parentBlock.find('.shipping-method-card.choice .shipping-method-title input[type="radio"]:checked + .label'));
            var type = typeObj.control.defaultValue;
            var price = parentBlock.find('.shipping-method-card.choice input[type="radio"]:checked').closest('.shipping-method-card.choice').find('.shipping-method-price').text();
            price = (price.trim()).replace(/\$|,/gi, "");
            var signatureRequired = "";
            var signatureRequiredInput = $(".signature-required-checkbox input");
            if(signatureRequiredInput[0]){
                signatureRequired = signatureRequiredInput[0].value == "true" ? "Yes" : "No";
            }
            return {order_shipping_type:type, order_shipping_amount:price, signature_required:signatureRequired};
        },

        _addRegionToAddress: function(data){
            var regionCode = data.region_id;
            var select = document.querySelector('select[name="region_id"]');
            var option = select.querySelector('option[value="' + regionCode + '"]');
            return _.extend(data, {region:option.innerText});
        },

        _validateStep: function (step) {
            if (step.hasClass('shipping')) {
                return this._validateShipping();
            } else if (step.hasClass('shipping-method')) {
                return this._validateShippingMethod();
            } else if (step.hasClass('payment-methods')) {
                return this._validatePaymentMethod();
            }
        },

        _validateShipping: function () {
            var isValid = true;
            if ($('.onestep-shipping-address .shipping-address-item').length > 0) {
                if ($('.new-shipping-address-form').hasClass('active')) {
                    return false; // logged in with addresses, but new address form is open
                }
                //return $('.onestep-shipping-address .shipping-address-item.selected-item').length > 0;
            } else {
                var provider = registry.get('checkoutProvider');
                _.each(['checkout.shippingAddress'], function (query) {
                    var addressComponent = registry.get(query);
                    addressComponent.validate();
                    if (provider.get('params.invalid')) {
                        isValid = false;
                        addressComponent.focusInvalid();
                    }
                    if($('.onestep-shipping-address input[name="telephone"]').val().length < 14) {
                        isValid = false;
                        $('.onestep-shipping-address input[name="telephone"]').closest('.field.field-phone').addClass('_error').append('<div class="mage-error" generated="true">Please enter valid phone number.</div>');
                    }
                }, this);
                //if (quote.shippingAddress().regionId > 0 && quote.shippingAddress().region === '') {
                //    $.each(window.registry.get('checkoutProvider')['dictionaries']['region_id'], function(i, region) {
                //        if (region.value === quote.shippingAddress().regionId) {
                //            quote.shippingAddress().region = region['title'];
                //            return false;
                //        }
                //    });
                //}
                shippingRateValidator.validateFields();
            }
            if (window.checkoutConfig.quoteData.gift_order == '1') {
                var recipientEmail = $('.aw-onestep-gift-message input[name="gift_recipient_email"]');
                if (recipientEmail.val().length < 1) {
                    isValid = false;
                    recipientEmail.closest('.field.recipient-field').removeClass('_error').find('.mage-error').remove();
                    recipientEmail.closest('.field.recipient-field').addClass('_error').append('<div class="mage-error" generated="true">Required</div>');
                } else if (!this._validateEmailRecipient(recipientEmail.val())) {
                    isValid = false;
                    recipientEmail.closest('.field.recipient-field').removeClass('_error').find('.mage-error').remove();
                    recipientEmail.closest('.field.recipient-field').addClass('_error').append('<div class="mage-error" generated="true">Please enter a valid email address.</div>');
                }
            }
            return isValid;
        },


        _validateEmailRecipient: function (email) {
            const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(String(email).toLowerCase());
        },

        _validateShippingMethod: function () {
            return $('.shipping-method-card.choice input.radio:checked').length > 0;
        },

        _validatePaymentMethod: function () {
            var methodListComponent = registry.get('checkout.paymentMethod.methodList');
            if (quote.paymentMethod()) {
                var methodCode = quote.paymentMethod().method;
                var methodRenderer = methodListComponent.getChild(methodCode);
                this._scrollToOrderSummary();
                return paymentValidationInvoker.invokeValidate(methodRenderer, methodCode);
            } else {
                if (!methodListComponent.validate()) {
                    methodListComponent.scrollInvalid();
                    return $.Deferred().reject();
                }
                return $.Deferred().resolve();
            }
        },

        _scrollToOrderSummary: function() {
            var self = this;

            mediaCheck({
                media: '(min-width: 1024px)',
                entry: function () {
                    $('.aw-onestep.aw-onestep-sidebar').css('margin-bottom', '0px');
                },
                exit: function () {

                    let orderSummaryId = $('.aw-onestep.aw-onestep-sidebar');
                    let screenHeight = $( window ).height();
                    let orderSummaryHeight = orderSummaryId.outerHeight();
                    if(screenHeight > orderSummaryHeight) {
                        let marginSize = screenHeight - orderSummaryHeight + 'px';
                        orderSummaryId.css('margin-bottom', marginSize);
                    }

                    $('.js-toggle-cart-items').trigger('click');

                    setTimeout(function () {
                        self.scrollTo('.aw-onestep.aw-onestep-sidebar');
                    }, 150)

                }
            });
        },

        _validateBillingAddress: function () {
            var isValid = true;
            var provider = registry.get('checkoutProvider');
            _.each(['checkout.paymentMethod.billingAddress'], function(query) {
                var addressComponent = registry.get(query);
                addressComponent.validate();
                if (provider.get('params.invalid')) {
                    isValid = false;
                    addressComponent.focusInvalid();
                }
                if(($('#step-payment-method input[name="telephone"]').length) && ($('#step-payment-method input[name="telephone"]').val().length < 14)) {
                    isValid = false;
                    $('#step-payment-method input[name="telephone"]').closest('.field.field-phone').addClass('_error').append('<div class="mage-error" generated="true">Please enter valid phone number.</div>');
                }
            }, this);
            return isValid;
        },

        serializedInputToJson: function(serializedInput) {
            var jsonObj = {};
            $.each(serializedInput, function(indx, elem) {
                if (elem.name == 'street[0]') {
                    jsonObj['street'] = elem.value;
                } else if (elem.name == 'street[1]') {
                    jsonObj['street'] += ' '+elem.value;
                } else {
                    jsonObj[elem.name] = elem.value;
                }
            });
            return jsonObj;
        },

        _validateShippingBtn: function () {
            var isValid = true;
            $.each($('.onestep-shipping-address').find('input:visible'), function () {
                //console.log($(this));
                if (!$(this).val() && $(this).attr('aria-required') == 'true') {
                    isValid = false;
                }
                if(window.checkoutConfig.quoteData.gift_order == '1'){
                    if($('.aw-onestep-gift-message input[name="gift_recipient_email"]').val().length < 1){
                        isValid = false;
                    }
                }
            });
            return isValid;
        },

        _enableShippingButtonforLoggedIn: function() {
            if (window.checkoutConfig.quoteData.gift_order == '1') {
                if ($('.onestep-shipping-address.active').length < 1 && $('.aw-onestep-gift-message input[name="gift_recipient_email"]').val()) {
                    $('.aw-onestep-groups_item.shipping .checkout-next-step').removeClass('inactive');
                }
                var widget = this;
                if(window.checkoutConfig.isCustomerLoggedIn == true){
                    $(document.body).on('keyup keypress blur', $('.aw-onestep-gift-message input[name="gift_recipient_email"]'), function() {
                        clearTimeout(this.recipientTimeout);
                        this.recipientTimeout = setTimeout(function() {
                            var recipientEmail = $('.aw-onestep-gift-message input[name="gift_recipient_email"]');
                            if (recipientEmail.val().length < 1) {
                                $('.aw-onestep-groups_item.shipping .checkout-next-step').addClass('inactive');
                                recipientEmail.closest('.field.recipient-field').removeClass('_error').find('.mage-error').remove();
                                recipientEmail.closest('.field.recipient-field').addClass('_error').append('<div class="mage-error" generated="true">Required</div>');
                            } else if (!widget._validateEmailRecipient(recipientEmail.val())) {
                                $('.aw-onestep-groups_item.shipping .checkout-next-step').addClass('inactive');
                                recipientEmail.closest('.field.recipient-field').removeClass('_error').find('.mage-error').remove();
                            } else {
                                recipientEmail.closest('.field.recipient-field').removeClass('_error').find('.mage-error').remove();
                                $('.aw-onestep-groups_item.shipping .checkout-next-step').removeClass('inactive');
                            }
                        },500);
                    });
                }
            } else {
                if ($('.onestep-shipping-address.active').length < 1) {
                    $('.aw-onestep-groups_item.shipping .checkout-next-step').removeClass('inactive');
                }
            }
        },

        _adjustFlowForGiftOrder: function() {
            console.debug("Adjusting flow for gift order...");
            if(!this._isGiftOrder()){
                return;
            }

            this._replaceShippingText("Recipient's info");
        },

        _isGiftOrder: function() {
            var giftOrder = window.checkoutConfig.quoteData.gift_order;
            if(!giftOrder || giftOrder === "0"){
                return false
            }

            return true;
        },

        _replaceShippingText: function(text) {
            var shippingSectionTitle = document.querySelector('.js-shipping-step-title');
            if(shippingSectionTitle){
                shippingSectionTitle.innerHTML = text;
            }
        }

    });
    return $.mage.nextStep;
});
