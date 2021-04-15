define([
    'jquery',
    'underscore',
    'mage/mage',
    'mage/calendar',
    'moment',
    'mask'
], function($, _, mage, calendar, moment, mask) {
    'use strict';
    $.widget('mage.thirdPartyLanding', {
        options: {},
        _validSerialLength: 21,
        _serialNumberIsValid: false,
        _customerEmailIsValid: false,
        _passwordErrorMessage :'',
        _salesChannel: null,
        _serialNumber: null,
        _purchaseDate: null,
        _giftOrder: false,
        _testSerial: "AAACBBDEEFFGGHHHHHH",
        _testSerial2: "MH1MSHA190701000085",
        _customerIsLoggedIn: false,

        //TODO - invalid login needs to show error
        _create: function() {
            this._initializeEventListeners();
            this._initializeClickListeners();
            this._initializeCalendar();
            this._addSerialAndDateMask();
            this._activateDeviceNextButton();
            this._activateLoginNextStep();
            this._createAccountLink();
            if (this._isCustomerLoggedIn()) {
                this._customerIsLoggedIn = true;
                this._handleLoggedInCustomer();
            }
        },

        _initializeEventListeners: function() {
            var widget = this;
            $(document.body).on('submit', '#signin-form', function(event) {
                event.preventDefault();
                var errors = widget._validateSignInForm();
                if (errors.length > 0) {
                    return widget._showValidationErrors(errors);
                }
                var obj = {
                    username: $('#third-party-landing-signin-email').val(),
                    password: $('#third-party-landing-signin-password').val()
                };
                if ($(this).find('.g-recaptcha-response').length == 1) {
                    obj['g-recaptcha-response'] = $(this).find('.g-recaptcha-response').val();
                }
                widget._clearAllErrorMessages();
                widget._clearSignInErrors();
                var form = $(this);
                $.ajax({
                    type: 'POST',
                    url: window.BASE_URL + 'customer/ajax/login',
                    showLoader: true,
                    data: JSON.stringify(obj),
                    success: function(response) {
                        if (!response.errors) {
                            return widget._handleLoginCustomer(obj.username);
                        }
                        widget._showSigninErrors(response);
                    },
                    error: function(err) {
                        widget._showSigninErrors();
                        console.log(err);
                    },
                    complete: function() {
                        form.find('input[name="token"]').val('');
                    }
                });
            });
            if (!$('#third-party-landing-serial-number').hasClass('gift-serial')) {
                $('#third-party-landing-serial-number').on('focusout', function (event) {
                    widget._validateSerialNumber('#third-party-landing-serial-number');
                });
            }
            $('#third-party-landing-email').on('focusout', function(event) {
                widget._validateEmail('#third-party-landing-email');
            });
            $('#third-party-landing-purchase-date').on('focusout', function(event) {
                widget._clearElementErrorMessage(document.querySelector("#third-party-landing-purchase-date"));
                if (!widget._purchaseDateIsValid('#third-party-landing-purchase-date')) {
                    return widget._addErrorMessageToElement("#third-party-landing-purchase-date", "Please enter a valid date.");
                }
            });
            $('#third-party-landing-firstname').on('focusout', function(event) {
                widget._clearElementErrorMessage(document.querySelector("#third-party-landing-firstname"));
                if (!widget._firstnameIsValid('#third-party-landing-firstname')) {
                    return widget._addErrorMessageToElement("#third-party-landing-firstname", "Required");
                }
            });
            $('#third-party-landing-lastname').on('focusout', function(event) {
                widget._clearElementErrorMessage(document.querySelector("#third-party-landing-lastname"));
                if (!widget._lastnameIsValid('#third-party-landing-lastname')) {
                    return widget._addErrorMessageToElement("#third-party-landing-lastname", "Required");
                }
            });
            $('#third-party-landing-password').on('focusout', function(event) {
                widget._clearElementErrorMessage(document.querySelector("#third-party-landing-password"));
                if (!widget._passwordIsValid('#third-party-landing-password')) {
                    return widget._addErrorMessageToElement("#third-party-landing-password",
                        "Password must be a minimum of 8 characters, 1 uppercase, 1 lowercase, 1 number and 1 special character.");
                }
            });
            $('#third-party-landing-confirm-password').on('focusout', function(event) {
                widget._clearElementErrorMessage(document.querySelector("#third-party-landing-confirm-password"));
                if (!widget._passwordsAreTheSame('#third-party-landing-password', '#third-party-landing-confirm-password')) {
                    return widget._addErrorMessageToElement("#third-party-landing-confirm-password", "Passwords don't match.");
                }
            });
            $('#third-party-landing-purchase-date').on('keyup', function(event) {
                if ($(this).val().length == 10) {
                    widget._clearElementErrorMessage(document.querySelector("#third-party-landing-purchase-date"));
                }
            });
            $('#third-party-landing-serial-number').on('keyup', function(event) {
                if ($(this).val().length == 21) {
                    widget._clearElementErrorMessage(document.querySelector("#third-party-landing-serial-number"));
                }
            });
            $('#third-party-landing-email').on('keyup', function(event) {
                widget._clearElementErrorMessage(document.querySelector("#third-party-landing-email"));
            });
            $('#third-party-landing-password').on('keyup', function(event) {
                widget._clearElementErrorMessage(document.querySelector("#third-party-landing-password"));
            });
            $('#third-party-landing-confirm-password').on('keyup', function(event) {
                widget._clearElementErrorMessage(document.querySelector("#third-party-landing-confirm-password"));
            });
            $('#third-party-landing-firstname').on('keyup', function(event) {
                widget._clearElementErrorMessage(document.querySelector("#third-party-landing-firstname"));
            });
            $('#third-party-landing-lastname').on('keyup', function(event) {
                widget._clearElementErrorMessage(document.querySelector("#third-party-landing-lastname"));
            });
            $('#third-party-landing-signin-email').on('keyup', function(event) {
                widget._clearElementErrorMessage(document.querySelector("#third-party-landing-signin-email"));
            });
            $('#third-party-landing-signin-password').on('keyup', function(event) {
                widget._clearElementErrorMessage(document.querySelector("#third-party-landing-signin-password"));
            });
            // TODO: next button validation. RN has only basic functionality
            $('.js-device-next').on('click', function() {
                if (!$(this).hasClass('inactive')) {
                    if (widget._customerIsLoggedIn) {
                        widget._submitForm(); // This becomes a submit
                    } else {
                        widget._sendTealiumNextStepEvent();
                        if ($(this).hasClass('gift-next')) {
                            widget._validateSerialNumber('#third-party-landing-serial-number');
                        } else {
                            widget._handleNextStep(this);
                        }
                    }
                }
            });
            $('.js-device-edit').on('click', function() {
                $('.subscription-thirdparty-step.create-account').removeClass('active');
                $(this).closest('.subscription-thirdparty-step').removeClass('done').addClass('active');
            });
        },

        _initializeClickListeners: function() {
            var widget = this;
            $('.js-third-party-landing-register').on('click', function(event) {
                event.preventDefault();
                var errors = widget._validateForm();
                if (errors.length > 0) {
                    return widget._showValidationErrors(errors);
                }
                widget._clearAllErrorMessages();
                widget._submitForm();
            });
            $('.js-third-party-landing-signin').on('click', function(event) {
                if($(this).parent('.js-user-already-exists').length){
                    $('#third-party-landing-signin-email').val($('#third-party-landing-email').val()).trigger('change');
                }
                event.preventDefault();
                widget._showSignInForm();
            });
            $('.js-third-party-landing-signout').on('click', function(event) {
                event.preventDefault();
                widget._logoutCustomer();
            });
        },

        _initializeCalendar: function() {
            $('#third-party-landing-purchase-date').calendar({
                showsTime: false,
                hideIfNoPrevNext: true,
                buttonText: 'Select Date',
                maxDate: '+1 d',
                minDate: new Date('01/27/20')
            });
        },

        _isCustomerLoggedIn: function() {
            return window.isLoggedIn;
        },

        _handleLoggedInCustomer: function() {
            this._customerEmailIsValid = true;
            this._setDeviceBoxMode();
            this._hideAccountForm();
        },

        /* Utility */
        _clearForm: function(query, selectIgnoreIds = []) {
            var form = document.querySelector(query);
            var inputs = form.querySelectorAll('input');
            for (var i = 0; i < inputs.length; i++) {
                inputs[i].value = "";
                var fieldNode = inputs[i].parentNode;
                fieldNode.classList.remove('fl-label-state');
                fieldNode.classList.add('fl-placeholder-state');
            }
            var checkboxes = form.querySelectorAll('checkbox');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = false;
            }
            var selects = form.querySelectorAll('select');
            for (var i = 0; i < selects.length; i++) {
                if (_.includes(selectIgnoreIds, selects[i].id)) {
                    continue;
                }
                selects[i].selectedIndex = 0;
            }
        },

        _showElement: function(query) {
            var element = document.querySelector(query);
            if (!element) {
                console.error("Couldn't find element: " + query);
            }
            element.classList.remove('hide');
        },

        _hideElement: function(query) {
            var element = document.querySelector(query);
            if (!element) {
                console.error("Couldn't find element: " + query);
            }
            element.classList.add('hide');
        },

        _disableFields: function($fields) {
            _.each($fields, function($query) {
                var element = document.querySelector($query);
                if (element) {
                    element.setAttribute('disabled', true);
                }
            });
        },

        _enableFields: function($fields) {
            _.each($fields, function($query) {
                var element = document.querySelector($query);
                if (element) {
                    element.removeAttribute('disabled');
                }
            });
        },

        _logoutCustomer: function() {
            var widget = this;
            $.ajax({
                type: 'GET',
                url: window.BASE_URL + "customer/ajax/logout",
                showLoader: true
            }).success(function(data) {
                if (data.message) {
                    return widget._handleLogoutCustomer();
                }

                widget._showLogoutErrors(data.errors);
            }).error(function(err) {
                console.log(err);
            });
        },

        _handleLoginCustomer: function(email) {
            window.isLoggedIn = true;
            this._customerIsLoggedIn = true;
            this._setSignedInValues(email);
            this._hideSignInForm();
            this._showCustomerInfoBox();
            this._hideAccountForm();
            this._setDeviceBoxMode();
            $('body').addClass('signin-visible');
        },

        _handleLogoutCustomer: function() {
            window.isLoggedIn = false;
            this._customerIsLoggedIn = false;
            this._setSignedInValues("");
            this._hideSignInForm();
            this._hideCustomerInfoBox();
            this._showAccountForm();
            this._setDeviceBoxMode();
            $('body').removeClass('signin-visible');
            $('.subscription-thirdparty-step.create-account').removeClass('active');
            $('.subscription-thirdparty-step.create-account .field').removeClass('hide');
        },

        _setSignedInValues: function(email) {
            document.querySelector('.js-third-party-landing__existing-customer-email').innerText = email;
        },

        _showSigninErrors: function(data) {
            var container = document.querySelector('.js-third-party-landing-signin-messages-container');
            container.querySelector('.js-third-party-landing-signin-error-message').innerHTML = (data ? data.message : 'There was an error signing in.');
            container.classList.remove('hide');
        },

        _clearSignInErrors: function() {
            var container = document.querySelector('.js-third-party-landing-signin-messages-container');
            container.querySelector('.js-third-party-landing-signin-error-message').innerHTML = "";
            container.classList.add('hide');
        },

        _showGeneralErrors: function(data) {
            var container = document.querySelector('.js-third-party-landing-general-messages-container');
            container.querySelector('.js-third-party-landing-general-error-message').innerHTML = (data ? data.message : 'There was an error signing in.');
            container.classList.remove('hide');
        },

        _clearGeneralErrors: function() {
            var container = document.querySelector('.js-third-party-landing-general-messages-container');
            var messages = container.querySelector('.js-third-party-landing-general-error-message');
            messages.innerHTML = "";
            container.classList.add('hide');
        },

        _showSignOutErrors: function(errors) {
            console.log(errors);
        },

        _showCustomerInfoBox: function() {
            this._showElement('.js-third-party-landing__existing-customer-info-container');
            this._showElement('.js-signined-in');
        },

        _hideCustomerInfoBox: function() {
            this._hideElement('.js-third-party-landing__existing-customer-info-container');
            this._hideElement('.js-signined-in');
        },

        _showSignInForm: function() {
            this._showElement('.js-signin-form-container');
            this._hideElement('.tab-sign-in');
            this._hideElement('.subscription-thirdparty-step.create-account');
            $('.subscription-thirdparty-step.device-details').removeClass('active');
            $('.subscription-thirdparty-step.device-details').removeClass('done');
            $('body').addClass('signin-visible');
        },

        _hideSignInForm: function() {
            this._hideElement('.js-signin-form-container');
            this._showElement('.tab-sign-in');
            this._showElement('.subscription-thirdparty-step.create-account');
            $('.subscription-thirdparty-step.device-details').addClass('active');
            $('body').removeClass('signin-visible');
        },

        /* Create Device */
        _setSalesChannel: function(val) {
            document.querySelector('#third-party-landing-sales-channel').value = val;
            this._salesChannel = val;
        },

        _submitForm: function() {
            this._clearSignInErrors();
            var widget = this;
            $.ajax({
                type: 'POST',
                url: window.BASE_URL + "subscription/customer/createsubscriptionfromdevice",
                data: $('.js-third-party-landing__form').serialize(),
                showLoader: true
            }).success(function(data) {
                if (data.status == 'success') {
                    widget._setSuccessCookie(data.subscription_id);
                    return window.location.href = window.BASE_URL + "subscription/customer/autorefill"
                }
                if (data.status == 'error') {
                    return widget._handleSubscriptionError(data);
                }
            }).error(function(err) {
                console.log(err);
            });
        },

        _setSuccessCookie: function(subscriptionId) {
            mage.cookies.set("device_registration_success", subscriptionId);
        },

        _handleSubscriptionError: function(data) {
            if (data.existing_customer) {
                return this._showGeneralErrors(data);
            }
            this._showGeneralErrors({message: 'There was an error - please try again.'});
            console.log('There was an error - please try again.');
        },

        /* Validation */
        _validateForm: function() {
            this._clearAllErrorMessages();
            var errors = [];
            if (!this._serialNumberIsValid) {
                errors.push(['#third-party-landing-serial-number', "Please enter a valid serial number."]);
            }
            if (!this._purchaseDateIsValid('#third-party-landing-purchase-date')) {
                errors.push(['#third-party-landing-purchase-date', "Please enter a valid purchase date."]);
            }
            if (!this._isCustomerLoggedIn()) {
                if (!this._customerEmailIsValid) {
                    errors.push(['#third-party-landing-email', "Please enter a valid email address."]);
                }
                if (!this._passwordIsValid('#third-party-landing-password')) {
                    errors.push(['#third-party-landing-password', this._passwordErrorMessage]);
                }
                if (!this._passwordsAreTheSame('#third-party-landing-password', '#third-party-landing-confirm-password')) {
                    errors.push(['#third-party-landing-confirm-password', "Passwords don't match."]);
                }
                if (!this._firstnameIsValid('#third-party-landing-firstname')) {
                    errors.push(['#third-party-landing-firstname', "Required"]);
                }
                if (!this._lastnameIsValid('#third-party-landing-lastname')) {
                    errors.push(['#third-party-landing-lastname', "Required"]);
                }
                if (!this._termsAreAccepted('#third-party-landing-accept-terms')) {
                    errors.push(['#third-party-landing-accept-terms', "Required"]);
                }
            }
            return errors;
        },

        _validateSignInForm: function() {
            this._clearAllErrorMessages();
            var errors = [];
            if (!this._nativeEmailValidation(document.querySelector('#third-party-landing-signin-email').value)) {
                errors.push(['#third-party-landing-signin-email', "Please enter a valid e-mail."]);
            }
            if (!this._passwordIsValid('#third-party-landing-signin-password')) {
                errors.push(['#third-party-landing-signin-password', this._passwordErrorMessage]);
            }
            return errors;
        },

        _validateSerialNumber: function(query) {
            var serialInput = document.querySelector(query);
            var serialNumber = serialInput.value;
            this._clearElementErrorMessage(serialInput);
            if (!this._isXCharactersLong(serialNumber, this._validSerialLength) || !this._isAllAsciiChar(serialNumber)) {
                this._addErrorMessageToElement("#third-party-landing-serial-number", "Please enter a valid serial number.");
            } else {
                //This will propagate down
                var widget = this;
                $.ajax({
                    type: 'POST',
                    url: window.BASE_URL + "subscription/customer/checkserialnumber",
                    showLoader: true,
                    data: {serial_number: serialNumber}
                }).success(function(data) {
                    if (data.status == 'success') {
                        widget._handleValidSerial(serialNumber, data.sales_channel, data.gift_order);
                        if ($('.gift-next').length == 1) {
                            widget._handleNextStep($('.gift-next'));
                        }
                    } else {
                        widget._handleInvalidSerial(data.message);
                    }
                }).error(function(err) {
                    //TODO error handling
                    console.log(err);
                });
            }
        },

        _handleValidSerial: function(serialNumber, salesChannel, giftOrder) {
            var serialInput = document.querySelector('#third-party-landing-serial-number');
            this._serialNumberIsValid = true;
            this._serialNumber = serialInput.value;
            this._giftOrder = giftOrder;
            this._setSalesChannel(salesChannel);
            this._clearElementErrorMessage(serialInput);
        },

        _handleInvalidSerial: function(errorMessage) {
            this._serialNumberIsValid = false;
            this._serialNumber = null;
            this._setSalesChannel("");
            this._addErrorMessageToElement('#third-party-landing-serial-number', errorMessage);
        },

        _isXCharactersLong: function(string, checkLength) {
            return (string.length == checkLength);
        },

        _isAtLeastXCharacters: function(string, checkLength) {
            return (string.length >= checkLength);
        },

        _isAllAsciiChar: function(serialNumber) {
            var re = new RegExp('[^\\u0000-\\u007f]');
            return !re.test(serialNumber);
        },

        //serial already has dashes added by the input mask
        _addDashesToSerialNumber: function(serialNumber) {
            serialNumber = serialNumber.slice(0, 4) + "-" + serialNumber.slice(4);
            serialNumber = serialNumber.slice(0, 14) + "-" + serialNumber.slice(14);
            return serialNumber;
        },

        _purchaseDateIsValid: function(query) {
            var purchaseDateInput = document.querySelector(query);
            if (purchaseDateInput.value.length < 10) {
                return false;
            }
            var purchaseDate = moment(purchaseDateInput.value, "MM/DD/YYYY");
            var saleDate = new Date('01/27/2020');
            if (!purchaseDate) return false;
            var currentDate = new Date();
            currentDate.setDate(currentDate.getDate() + 1);
            var isSameOrBefore = purchaseDate.isSameOrBefore(currentDate, "day");
            var isSameOrAfter = purchaseDate.isSameOrAfter(saleDate, "day");
            if (isSameOrBefore && isSameOrAfter) {
                this._purchaseDate = purchaseDateInput.value;
                return true;
            }
            this._purchaseDate = null;
            return false;
        },

        _validateEmail: function(query) {
            var widget = this;
            var customerEmail = document.querySelector(query).value;
            if (!widget._nativeEmailValidation(customerEmail)) {
                return widget._addErrorMessageToElement(query, "Please enter a valid e-mail address.");
            }
            $.ajax({
                type: 'POST',
                url: window.BASE_URL + "rest/mlk_us_sv/V1/customers/isEmailAvailable",
                showLoader: true,
                headers: { 'Content-Type': 'application/json' },
                data: JSON.stringify({ customerEmail: customerEmail })
            }).success(function(data) {
                if (data) {
                    return widget._handleValidEmail(customerEmail);
                }
                widget._handleInvalidEmail(customerEmail);
            }).error(function(err) {
                //TODO error handling
                console.log(err);
            });
        },

        _handleValidEmail: function(customerEmail) {
            this._clearElementErrorMessage(document.querySelector('#third-party-landing-email'));
            this._customerEmailIsValid = true;
            this._hideUserAlreadyExists();
            this._enableFields([
                "#third-party-landing-password",
                "#third-party-landing-firstname",
                "#third-party-landing-lastname"
            ]);

        },

        _handleInvalidEmail: function(customerEmail) {
            // this._addErrorMessageToElement('#third-party-landing-email', "Email is already associated with an existing account.");
            this._disableFields([
                "#third-party-landing-password",
                "#third-party-landing-firstname",
                "#third-party-landing-lastname"
            ]);
            this._customerEmailIsValid = false;
            this._showUserAlreadyExists();
        },

        _showUserAlreadyExists: function() {
            this._showElement('.js-user-already-exists');
        },

        _hideUserAlreadyExists: function() {
            this._hideElement('.js-user-already-exists');
        },

        _passwordIsValid: function(query) {
            var password = document.querySelector(query).value.trim();
            if(!password){
                return false;
            }
            return this._isAtLeastXCharacters(password, 8)
                && this._hasOneUppercase(password)
                && this._hasOneLowerCase(password)
                && this._hasOneSpecialCharacter(password)
                && this._hasOneNumber(password);
        },

        _passwordsAreTheSame: function(queryOne, queryTwo) {
            var passwordOne = document.querySelector(queryOne).value;
            var passwordTwo = document.querySelector(queryTwo).value;
            return passwordOne === passwordTwo;
        },

        _nativeEmailValidation: function(email) {
            email = email.trim();
            if (!email) {
                return false;
            }
            return /^([a-z0-9,!\#\$%&'\*\+\/=\?\^_`\{\|\}~-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z0-9,!\#\$%&'\*\+\/=\?\^_`\{\|\}~-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*@([a-z0-9-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z0-9-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*\.(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]){2,})$/i.test(email);
        },

        _hasOneSpecialCharacter: function(str) {
            return str.match(/[!@#$%^&*(),.?":{}|<>]/gi);
        },

        _hasOneNumber: function(str) {
            return str.match(/[0-9]+/g);
        },

        _hasOneUppercase: function(str) {
            return str.match(/[A-Z]+/g);
        },

        _hasOneLowerCase: function(str) {
            return str.match(/[a-z]+/g);
        },

        _firstnameIsValid: function(query) {
            var firstname = document.querySelector(query).value;
            return this._isAtLeastXCharacters(firstname, 3);
        },

        _lastnameIsValid: function(query) {
            return this._isAtLeastXCharacters(document.querySelector(query).value, 2);
        },

        _termsAreAccepted: function(query) {
            return document.querySelector(query).checked;
        },

        /* Error messaging */
        _showValidationErrors: function(errors) {
            var widget = this;
            _.each(errors, function(error) {
                widget._addErrorMessageToElement(error[0], error[1]);
            });
        },

        _clearAllErrorMessages: function() {
            var errorMessages = document.querySelectorAll('.mage-error');
            this._passwordErrorMessage = '';
            _.each(errorMessages, function(error) {
                error.remove();
            });
        },

        _clearElementErrorMessage: function(element) {
            element.classList.remove('has-error');
            var closestError = element.parentNode.querySelector('.mage-error');
            if (closestError) {
                closestError.remove();
            }
        },

        _addErrorMessageToElement: function(elementQuery, errorMessage) {
            var element = document.querySelector(elementQuery);
            if (!element) {
                console.error("Can't find element that matches " + elementQuery);
            }
            this._clearElementErrorMessage(element);
            var errorElement = this._getErrorHtml(errorMessage);
            element.classList.add('has-error');
            element.parentNode.appendChild(errorElement);
        },

        _getErrorHtml: function(errorMessage) {
            var element = document.createElement('span');
            element.innerText = errorMessage;
            element.style.color = "red";
            element.classList.add('mage-error');
            return element;
        },

        _activateDeviceNextButton: function() {
            var widget = this;
            var btn = $('.device-details .js-device-next');
            var input = $('.device-details').find('input.input-text');
            $(document.body).on('keyup keypress change blur', input, function() {
                if (widget._validateDeviceStep() && widget._purchaseDateIsValid('#third-party-landing-purchase-date')) {
                    btn.removeClass('inactive');
                } else {
                    btn.addClass('inactive');
                }
            });
        },

        _validateDeviceStep: function() {
            return $('#third-party-landing-serial-number').val() && $('#third-party-landing-purchase-date').val() && ($('#third-party-landing-serial-number').val().length == 21);
        },

        _activateLoginNextStep: function() {
            var btn = $('.js-third-party-landing__signin-form .js-third-party-landing-signin-submit');
            var input = $('.js-third-party-landing__signin-form').find('js-third-party-landing-input');
            $(document.body).on('keyup keypress change blur', input, function() {
                if ($('#third-party-landing-signin-email').val() && $('#third-party-landing-signin-password').val()) {
                    btn.removeClass('inactive');
                } else {
                    btn.addClass('inactive');
                }
            });
        },

        _handleNextStep: function(elm) {
            if (!$('#third-party-landing-serial-number').parents('.subscription-thirdparty-step').find('input').hasClass('has-error')) {
                let preview = $('#third-party-landing-serial-number').val();
                $(elm).closest('.subscription-thirdparty-step').removeClass('active').addClass('done');
                if (!$('#third-party-landing-purchase-date').hasClass('gift-signup-date')) {
                    preview += '<br>' + $('#third-party-landing-purchase-date').val();
                }
                $(elm).closest('.subscription-thirdparty-step').find('.group-preview').html(preview).removeClass('hide');
                $(elm).closest('.subscription-thirdparty-step').next('.subscription-thirdparty-step').addClass('active');
            }
        },

        //TODO - this needs attention
        _setDeviceBoxMode: function() {
            if (this._customerIsLoggedIn) {
                this._setDeviceButtonText('Submit');
                this._setDeviceStepText('2');
            } else {
                this._setDeviceButtonText('Next');
                this._setDeviceStepText('1');
            }
        },

        _setDeviceButtonText: function(text) {
            document.querySelector('.js-device-next').text = text;
        },

        _setDeviceStepText: function(text) {
            //TODO - Claudiu is this done with css?
        },

        _createAccountLink: function() {
            var widget = this;
            $('.js-create-account').on('click', function() {
                widget._showElement('.create-account');
                widget._hideSignInForm();
                $('.subscription-thirdparty-step.create-account').removeClass('active');
            });
        },

        _showAccountForm: function() {
            this._showElement('.create-account');
        },

        _hideAccountForm: function() {
            this._hideElement('.create-account');
        },

        _addSerialAndDateMask: function() {
            $('#third-party-landing-serial-number').mask('AAAA-AAAAAAAAA-AAAAAA');
            $('#third-party-landing-purchase-date').mask('00/00/0000');
        },

        _sendTealiumNextStepEvent: function() {
            if (this._serialNumberIsValid && !!this._serialNumber && !!this._purchaseDate) {
                var widget = this;
                var url = window.BASE_URL + "subscription/customer/sendtealiumdeviceregistration";
                $.ajax({
                    type: 'POST',
                    url: url,
                    showLoader: true,
                    data: {
                        serial_number: widget._serialNumber,
                        purchase_date: widget._purchaseDate,
                        sales_channel: widget._salesChannel,
                        gift_order: widget._giftOrder
                    }
                }).error(function(err) {
                    console.log(err);
                });
            }
        },

    });
    return $.mage.thirdPartyLanding;
});
