/**
 * Copyright 2019 aheadWorks. All rights reserved.
 * See LICENSE.txt for license details.
 */

define([
    'jquery',
    'mage/url',
    'uiComponent',
    'ko',
    'Magento_Customer/js/model/customer',
    'Magento_Customer/js/action/check-email-availability',
    'Magento_Customer/js/action/login',
    'Magento_Checkout/js/model/quote',
    'Aheadworks_OneStepCheckout/js/model/checkout-data',
    'Aheadworks_OneStepCheckout/js/model/newsletter/subscriber',
    'Aheadworks_OneStepCheckout/js/action/check-if-subscribed-by-email',
    'Magento_Checkout/js/model/full-screen-loader',
    'Aheadworks_OneStepCheckout/js/model/checkout-data-completeness-logger',
    'Aheadworks_OneStepCheckout/js/model/checkout-agreements/validator',
    'Magento_Checkout/js/model/authentication-messages',
    'Aheadworks_OneStepCheckout/js/model/gift-order',
    'mage/validation',
    'Magento_Customer/js/password-strength-indicator',
    'validation'
], function (
    $,
    baseUrl,
    Component,
    ko,
    customer,
    checkEmailAvailabilityAction,
    loginAction,
    quote,
    checkoutData,
    newsletterSubscriber,
    checkIfSubscribedByEmailAction,
    fullScreenLoader,
    completenessLogger,
    agreementValidator,
    messageContainer,
    giftOrder
) {
    'use strict';

    var validatedEmail = checkoutData.getValidatedEmailValue(),
        newsletterSubscribeConfig = window.checkoutConfig.newsletterSubscribe,
        verifiedIsSubscribed = checkoutData.getVerifiedIsSubscribedFlag();

    if (validatedEmail && !customer.isLoggedIn()) {
        quote.guestEmail = validatedEmail;
        if (newsletterSubscribeConfig.isGuestSubscriptionsAllowed) {
            newsletterSubscriber.subscriberEmail = validatedEmail;
            if (verifiedIsSubscribed !== undefined) {
                newsletterSubscriber.isSubscribed(verifiedIsSubscribed);
                newsletterSubscriber.subscribedStatusVerified(true);
            }
        }
    }

    return Component.extend({
        defaults: {
            template: 'Aheadworks_OneStepCheckout/form/email',
            email: checkoutData.getInputFieldEmailValue(),
            customerEmail: customerData.email,
            emailFocused: false,
            isLoading: false,
            isPasswordVisible: window.checkoutConfig.passwordRequired,
            isRegisterTabVisible: true,
            isLoginTabVisible: false,
            isSignInMessageVisable: false,
            isEmailUnique: false,
            userExists: false,
            listens: {
                email: 'emailHasChanged',
                emailFocused: 'validateEmail',
                password: 'passwordHasChanged',
                passwordFocused: 'validatePassword',
                confirmPassword: 'confirmPasswordHasChanged',
                confirmPasswordFocused: 'validatePasswordConfirmation'
            }
        },
        checkDelay: 2000,
        checkAvailabilityRequest: null,
        checkIfSubscribedRequest: null,
        isCustomerLoggedIn: customer.isLoggedIn,
        forgotPasswordUrl: window.checkoutConfig.forgotPasswordUrl,
        emailCheckTimeout: 0,
        requireLogin: window.checkoutConfig.customersMustLogin,
        deviceInCart: window.checkoutConfig.deviceInCart,
        autocomplete: checkoutConfig.autocomplete,
        giftOrder: giftOrder.isGiftOrder(),
        email: ko.computed(checkoutData.getInputFieldEmailValue),

        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super();
            completenessLogger.bindField('email', this.email);
            this.checkEmailAvailability();
        },

        /**
         * @inheritdoc
         */
        initObservable: function () {
            this._super()
                .observe([
                    'email',
                    'emailFocused',
                    'password',
                    'passwordFocused',
                    'confirmPassword',
                    'confirmPasswordFocused',
                    'isLoading',
                    'isPasswordVisible',
                    'isRegisterTabVisible',
                    'isLoginTabVisible',
                    'isSignInMessageVisable',
                    'isEmailUnique',
                    'userExists'
                ]);

            return this;
        },

        buildUrl: function ($url) {
            return url.build($url);
        },

        setUserExists: function ($doesExists) {
            this.userExists($doesExists);

            if ($doesExists) {
                $('.features-line .affirm-on, .requires-signup.affirm-on').show().addClass('sticky');
                $('.features-line .affirm-off, .payment-finance, .payment-cc').hide();
            } else {
                $('.features-line .affirm-on, .requires-signup.affirm-on').hide().removeClass('sticky');
                $('.features-line .affirm-off').show();
            }
        },

        /**
         * Process email value change
         */
        emailHasChanged: function () {
            var self = this;

            this.setUserExists(false);
            this.isEmailUnique(false);
            this.isSignInMessageVisable(false);
            clearTimeout(this.emailCheckTimeout);

            if (self.email() && self.validateEmail()) {
                quote.guestEmail = self.email();
                newsletterSubscriber.subscriberEmail = self.email();
                checkoutData.setValidatedEmailValue(self.email());
            }
            this.emailCheckTimeout = setTimeout(function () {
                if (self.validateEmail()) {
                    self.checkEmailAvailability();
                    if (newsletterSubscribeConfig.isGuestSubscriptionsAllowed) {
                        self.checkIfSubscribedByEmail();
                    }
                } else {
                    newsletterSubscriber.subscribedStatusVerified(false);
                }
            }, self.checkDelay);

            checkoutData.setInputFieldEmailValue(self.email());
        },

        /**
         * Process password value change
         */
        passwordHasChanged: function () {
            var self = this;
            checkoutData.setInputFieldPasswordValue(self.password());
        },

        /**
         * Process confirm password value change
         */
        confirmPasswordHasChanged: function () {
            var self = this;
            checkoutData.setInputFieldConfirmPasswordValue(self.confirmPassword());
        },

        /**
         * Validate password
         */
        validatePassword: function (focused) {
            var loginFormSelector = 'form[data-role=email-with-possible-login]',
                passwordSelector = loginFormSelector + ' input[name=customer-password]',
                loginForm = $(loginFormSelector),
                validator;

            loginForm.validation();
            if (focused === false && !!this.password()) {
                return !!$(passwordSelector).valid();
            }
            validator = loginForm.validate();

            return validator.check(passwordSelector);
        },

        /**
         * Validate confirm password and set password on quote
         */
        validatePasswordConfirmation: function (focused) {
            var loginFormSelector = 'form[data-role=email-with-possible-login]',
                confirmPasswordSelector = loginFormSelector + ' input[name=confirm-customer-password]',
                loginForm = $(loginFormSelector),
                validator,
                isValid;

            loginForm.validation();
            if (focused === false && !!this.confirmPassword()) {
                isValid = !!$(confirmPasswordSelector).valid();
            } else {
                validator = loginForm.validate();
                isValid = validator.check(confirmPasswordSelector);
            }
            if(isValid){
                quote.guestPassword = this.password();
            }
            return isValid;
        },

        showLogin: function () {
            this.isLoginTabVisible(true);
            this.isRegisterTabVisible(false);
        },

        showCreateAccount: function () {
            this.isEmailUnique(false);
            this.isSignInMessageVisable(false);
            this.email(null);
            checkoutData.setInputFieldEmailValue(null);
            this.isRegisterTabVisible(true);
            this.isLoginTabVisible(false);
            $("[data-container=password]").passwordStrengthIndicator();
        },

        /**
         * Check email availability
         */
        checkEmailAvailability: function () {
            var self = this,
                isEmailCheckComplete = $.Deferred();

            this._validateRequest(this.checkAvailabilityRequest);
            this.isLoading(true);
            this.checkAvailabilityRequest = checkEmailAvailabilityAction(isEmailCheckComplete, this.email());

            $.when(isEmailCheckComplete).done(function () {
                self.isEmailUnique(true);
                $("[data-container=password]").passwordStrengthIndicator();
            }).fail(function () {
                //self.showLogin();
                self.isSignInMessageVisable(true);
                if (!this.requireLogin) {
                    self.setUserExists(true);
                    self.isEmailUnique(true);
                }
            }).always(function () {
                self.isLoading(false);
            });
        },

        /**
         * Check if subscribed by email
         */
        checkIfSubscribedByEmail: function () {
            var isEmailCheckComplete = $.Deferred();

            this._validateRequest(this.checkIfSubscribedRequest);
            this.checkIfSubscribedRequest = checkIfSubscribedByEmailAction(isEmailCheckComplete, this.email());

            $.when(isEmailCheckComplete).done(function () {
                newsletterSubscriber.isSubscribed(true);
                checkoutData.setVerifiedIsSubscribedFlag(true);
            }).fail(function () {
                newsletterSubscriber.isSubscribed(false);
                checkoutData.setVerifiedIsSubscribedFlag(false);
            }).always(function () {
                newsletterSubscriber.subscribedStatusVerified(true);
            });
        },

        /**
         * If request has been sent abort it
         *
         * @param {XMLHttpRequest} request
         */
        _validateRequest: function (request) {
            if (request != null && $.inArray(request.readyState, [1, 2, 3])) {
                request.abort();
                request = null;
            }
        },

        /**
         * Local email validation
         *
         * @param {Boolean} focused
         * @returns {Boolean}
         */
        validateEmail: function (focused) {
            var loginFormSelector = 'form[data-role=email-with-possible-login]',
                usernameSelector = loginFormSelector + ' input[name=username]',
                loginForm = $(loginFormSelector),
                validator;

            loginForm.validation();
            if (focused === false && !!this.email()) {
                return !!$(usernameSelector).valid();
            }
            validator = loginForm.validate();

            return validator.check(usernameSelector);
        },

        /**
         * Perform login action
         * @param {Object} loginForm
         */
        login: function(loginForm) {
            var loginData = {},
                formDataArray = $(loginForm).serializeArray();
            $.each(formDataArray, function () {
                loginData[this.name] = this.value;
            });
            if ($(loginForm).validation() && $(loginForm).validation('isValid')) {
                fullScreenLoader.startLoader();
                loginAction(loginData, checkoutConfig.checkoutUrl, undefined, messageContainer).always(function() {
                    fullScreenLoader.stopLoader();
                });
            }
        },

        /**
         * runs on click event of the 'Next' button
         */
        validateAccountStep: function() {
            var loginForm = $('form[data-role="email-with-possible-login"]'),
                email = $('.aw-onestep-groups_item.account').find('input[type="email"]'),
                passwordValid = true;
            if(email.val()){
                this.isSignInMessageVisable(false);
                loginForm.validation();
                loginForm.validation('isValid');
                if (window.checkoutConfig.passwordRequired) {
                    passwordValid = $('#customer-password').data('score') > 2;
                }
                if (!email.hasClass('is_unique')) { // email field does not have the class for passing validation...
                    if ($('#customer-email-fieldset').find('.mage-error:visible').length == 0) { // and there is no displayed error, meaning it hasn't failed the check yet
                        var self = this,
                            isEmailCheckComplete = $.Deferred();
                        this._validateRequest(this.checkAvailabilityRequest);
                        this.isLoading(true);
                        this.checkAvailabilityRequest = checkEmailAvailabilityAction(isEmailCheckComplete, this.email());
                        $.when(isEmailCheckComplete).done(function () {
                            self.isEmailUnique(true);
                            $("[data-container=password]").passwordStrengthIndicator();
                        }).fail(function () {
                            self.isSignInMessageVisable(true);
                        }).always(function () {
                            self.isLoading(false);
                            if (email.hasClass('is_unique') && agreementValidator.validate() && passwordValid) {
                                self._addAccountPreview();
                            }
                        });
                    }
                } else if (agreementValidator.validate() && passwordValid) {
                    this._addAccountPreview();
                }
            } else {
                loginForm.validation();
                loginForm.validation('isValid');
            }
        },

        _setAccountPreview: function(parentBlock) {
            parentBlock.find('.group-title').after(this._getAccountPreviewText());
            var giftOrderInput = document.querySelector('.js-gift-order-preview-input');
            if(!giftOrderInput){
                return;
            }
            giftOrderInput.checked = true;
            $('.js-gift-order-preview-input').on('change', function(){
                giftOrder.handleGiftOrderClick();
            })
        },

        _getAccountPreviewText: function() {
            var isGiftOrder = giftOrder.isGiftOrder();

            var groupPreview = document.createElement('div');
            groupPreview.classList.add("group-preview");
            var customerEmail = $('#customer-email').val();

            if(!isGiftOrder){
                groupPreview.innerHTML = 
                    customerEmail + '<br />'
                    + (window.checkoutConfig.passwordRequired ? '<span class="pass">••••••••</span>' : '');
                return groupPreview;
            }

            var giftOrderBox = this._getGiftOrderTextBox();
            groupPreview.appendChild(giftOrderBox);

            groupPreview.innerHTML = groupPreview.innerHTML
                + customerEmail + '<br />'
                + (window.checkoutConfig.passwordRequired ? '<span class="pass">••••••••</span>' : '');
            return groupPreview;
        },

        //Easier to build a new one, since it is only a fancy link
        _getGiftOrderTextBox: function() {
            var container = document.createElement('div');
            container.classList.add('gift-preview');
            
            var checkbox = document.createElement('input');
            checkbox.classList.add('js-gift-order-preview-input');
            checkbox.type = "checkbox";
            checkbox.id = "gift-order-preview-input";
            
            var label = document.createElement('label');
            label.innerHTML = '<span>This is a gift</span>';
            label.setAttribute("for", "gift-order-preview-input");

            container.appendChild(checkbox);
            container.appendChild(label);
            return container;
        },

        _addAccountPreview: function() {
            var parentBlock = $('.aw-onestep-groups_item.account');
            parentBlock.find('.group-preview').remove();
            parentBlock.removeClass('active').addClass('done');
            $('.aw-onestep-groups_item').not(parentBlock).not('.done').first().addClass('active');
            $(document).trigger('tealiumEventCustomerInfoStepNewCustomer', [$('#customer-email').val()]);
            this._setAccountPreview(parentBlock);
            parentBlock.find('.checkout-next-step').html('Save');
            parentBlock.find('.checkout-edit-step-cancel').hide();
            this._activateShippingBtn();
            setTimeout(function() {
                $('#step-shipping-address').get(0).scrollIntoView(
                    {
                        behavior: 'smooth',
                        block: 'start',
                        inline: 'start'
                    }
                );
            }, 100);
        },

        _activateShippingBtn: function() {
            var _self = this;
            var btn = $('.aw-onestep-groups_item.shipping .checkout-next-step');
            var parentBlock = $('.aw-onestep-groups_item.shipping');
            var input = parentBlock.find('input[aria-required=true]');
            $(document.body).on('keyup keypress change blur', input, function() {
                setTimeout(function(){
                    //console.log(_self._validateShippingBtn());
                    if (_self._validateShippingBtn() && _self._validateRecipientEmail()) {
                        btn.removeClass('inactive');
                    } else {
                        btn.addClass('inactive');
                    }
                },500);
            });

            if(($('.aw-onestep-groups_item.shipping').hasClass('active')) && _self._validateShippingBtn() && _self._validateRecipientEmail()){
                btn.removeClass('inactive');
            } else {
                btn.addClass('inactive');
            }
        },

        _validateShippingBtn: function () {
            if ($('.onestep-shipping-address .shipping-address-item').length > 0) {
                if ($('.new-shipping-address-form').hasClass('active')) {
                    return false; // logged in with addresses, but new address form is open
                }

                return $('.onestep-shipping-address .shipping-address-item.selected-item').length > 0;
            } else {
                var isValid = false;
                $.each($('.aw-onestep-groups_item.shipping.active form.form').find('input:visible'), function () {
                    if ((!$(this).val() && $(this).attr('aria-required') == 'true') || $('.aw-onestep-groups_item.shipping.active .field._error').length > 0 || ($('.aw-onestep-groups_item.shipping input[name="telephone"]').val().length < 14)) {
                        isValid = false;
                    } else {
                        isValid = true;
                    }
                });
                return isValid;
            }
        },

        _validateRecipientEmail: function () {
            var isValid = false;
            if(window.checkoutConfig.quoteData.gift_order == '1'){
                if($('.aw-onestep-gift-message input[name="gift_recipient_email"]').val()){
                    isValid = true;
                    $('.aw-onestep-gift-message input[name="gift_recipient_email"]').closest('.field.recipient-field').removeClass('_error').find('.mage-error').remove();
                }
            } else {
                isValid = true;
            }
            return isValid;
        },

        showSignInFields: function() {
            if(giftOrder.isGiftOrder()){
                return false;
            }

            if(window.checkoutConfig.deviceInCart){
                return true;
            }

            return false;
        }
    });
});
