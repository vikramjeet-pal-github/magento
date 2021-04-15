define([
    'jquery',
    'underscore',
    'Magento_Ui/js/modal/confirm',
    'mage/translate',
    'https://maps.googleapis.com/maps/api/js?key=' + window.googleAutocompleteApiKey + '&libraries=places'
], function (
    $,
    _,
    confirm,
    $t) {
    'use strict';

    $.widget('molekule.successPageForm', {

        /**
         * @private
         */
        _create: function () {
            let self = this;
            self._ccFieldsCompleted = {
                cardNumber: false,
                cardExpiry: false,
                cardCvc: false
            };
            self.shippingAddress = self.options.shippingAddress;
            self.billingAddress = self.options.billingAddress;
            self.getCustomerFieldsFromSession = self.options.getCustomerFieldsFromSession;
            self.isResidential = self.options.isResidential;
            self.getAffirmFlowItems = self.options.getAffirmFlowItems;
            self.orderId = self.options.orderId;
            self.address = {};

            this._actions();
            this._addEventListeners();

            self._newBillingAddressAutocomplete,
            self._savedNewBillingAddressState = null;
            self._newBillingAddressFormQueryMap = {
                street: '#flow-billing-streetOne',
                locality: '#flow-billing-city',
                administrative_area_level_1: '.fl-label-state #state',
                country: '#flow-billing-country',
                postal_code: '#flow-billing-postcode'
            };
            self._initGoogleAutoComplete(
                'flow-billing-streetOne',
                self._newBillingAddressAutocomplete,
                self._newBillingAddressFormQueryMap,
                self._savedNewBillingAddressState
            );

            $(document).on('change','#flow-billing-country',function() {
                if(!self._savedNewBillingAddressState){
                    self._updateStateSelectOnCountryChange('#flow-billing-country', '.js-subscription-flow-new-billing-address-form #state');
                }
            });
        },

        /**
         * Collect all actions and initialize them
         * @private
         */
        _actions: function () {
            this._sameAsBillingAddress();
            this._cancelActivation();
            this._activateAutoRefills();
        },

        /**
         * Show and hide billing address form
         * @private
         */
        _sameAsBillingAddress: function () {
            let self = this;
            $('#sameasshipping').click(function () {
                if ($(this).is(':checked')) {
                    $('.subscription-flow-new-billing-address').hide();
                } else {
                    $('.subscription-flow-new-billing-address').show();
                }
            })
        },

        /**
         * Checkbox status "Billing address is same as shipping"
         * @return {boolean}
         * @private
         */
        _isSameAsBillingChecked: function () {
            if ($('#sameasshipping').is(':checked')) {
                return true;
            }
            return false;
        },

        /**
         * Swap content to default checkout success page
         * @private
         */
        _moveToSuccessPage: function() {
            $('body').removeClass('affirm-save-card-step');
            $('body').addClass('final-success');
            $('#affirm-save-cart-step').hide();
            $('.affirm-flow-footer').hide();
            $('#final-success-page').show();
            $(window).scrollTop(0);
            setTimeout(function() {
                $('.initial-loader').remove();
            }, 5000);
        },

        /**
         * Hide Affirm flow step and show standard success page
         * @private
         */
        _cancelActivation: function () {
            let self = this;
            $('.js-cancel-activate-subscription-new').click(function () {
                self._moveToSuccessPage();
                self._sendTealiumLaterClickEvent();
            });
        },

        /**
         * Insert Auto Refill message product item
         * @param status
         * @private
         */
        _insertAutoRefillMsg: function(status) {
            let divId = $('.item-details__short-description');
            if(status === 'active') {
                let message = $t('Auto-refills activated');
                let nextRefillDate = $t('Next refill:') + ' ' + $('#next-refill-date').data('next-refill');
                divId.find('.features-line').removeClass('affirm-on');
                divId.find('.features-line').prepend(message);
                divId.find('.features-line .u-warning-text').remove();
                divId.find('.u-warning-text').remove();
                divId.find('.next-refill').text(nextRefillDate);
            }

        },

        /**
         * Return Order ID
         * @return {string|molekule.successPageForm.options.orderId}
         * @private
         */
        _getOrderId: function () {
            if (this.orderId > 0) {
                return this.orderId
            }
            return '';
        },

        /**
         * Return address in format for POST on controller
         * @param formAddress
         * @return {{firstname: (*), city: (*), street: (*), postcode: (*), telephone: (*), region: (*), countryId: (*), lastname: (*)}}
         * @private
         */
        _getAddress: function (formAddress = {}) {
            let self = this;
            let currentAddress = self.shippingAddress;
            let sameAsShipping = true;
            if (!_.isEmpty(formAddress)) {
                sameAsShipping = false
            }
            if (!sameAsShipping) {
                var newFormAddress = formAddress['flow-billing-streetTwo'] !== ''
                    ? formAddress['flow-billing-streetOne'] + ', ' + formAddress['flow-billing-streetTwo']
                    : formAddress['flow-billing-streetOne'];
                var region = $("#state option:selected").text();
            }

            return {
                firstname: sameAsShipping ? currentAddress['firstname'] : formAddress['flow-billing-firstname'],
                lastname: sameAsShipping ? currentAddress['lastname'] : formAddress['flow-billing-lastname'],
                street: sameAsShipping ? currentAddress['street'] : newFormAddress,
                city: sameAsShipping ? currentAddress['city'] : formAddress['flow-billing-city'],
                region: sameAsShipping ? currentAddress['region'] : region,
                postcode: sameAsShipping ? currentAddress['postcode'] : formAddress['flow-billing-postcode'],
                countryId: sameAsShipping ? currentAddress['country'] : formAddress['flow-billing-country_id'],
                telephone: sameAsShipping ? currentAddress['telephone'] : formAddress['flow-billing-telephone']
            }
        },

        /**
         * Action button for activation Auto Refills
         * @private
         */
        _activateAutoRefills: function () {
            let self = this;
            $('.js-activate-subscription').click(function () {
                let billingForm = $('form.js-subscription-flow-new-billing-address-form');
                if (billingForm.validation() && billingForm.validation('isValid') && self._cardInputIsValid()) {
                    if (self._isSameAsBillingChecked()) {
                        self.address = {};
                        self.address = self._getAddress();
                    } else {
                        let newAddress = {};
                        let formAddress = billingForm.serializeArray();
                        if (formAddress.length > 0) {
                            _.each(formAddress, function (element) {
                                newAddress[element.name] = element.value;
                            })
                        }
                        self.address = {};
                        self.address = self._getAddress(newAddress);
                    }

                    return stripe.saveCard(this, function () {
                        $.ajax({
                            type: 'POST',
                            showLoader: true,
                            url: window.BASE_URL + '/checkout/onepage_success/affirmflow',
                            data: {
                                form_key: $('.js-add-card-formkey').val(),
                                payment: {cc_stripejs_token: stripe.token},
                                address: self.address,
                                order_id: self._getOrderId()

                            }
                        }).success(function (data) {
                            if (data.status === 'error') {
                                self._showMessage(data.status, data.message);
                                return;
                            }
                            if (data.status === 'success') {
                                self._moveToSuccessPage();
                                self._insertAutoRefillMsg('active');
                                self._sendTealiumActivateAutoRefillsClickEvent();
                            }
                        }).error(function (err) {
                            console.log(err);
                            self._showMessage('error', 'There was an error, please try again');
                        });
                    });
                }
            });
        },

        /**
         * Tealium event for button "I’ll do this later"
         * @private
         */
        _sendTealiumLaterClickEvent: function() {
            let self = this;
            let splitAddress = self.shippingAddress['street'].split("\n");
            var utag_data = {
                tealium_event: "clicked_affirm_later",
                event_category: "Ecommerce",
                event_action: "Affirm Payment Capture",
                customer_address_1_shipping: splitAddress[0],
                customer_address_2_shipping: splitAddress[1],
                customer_city_shipping: self.shippingAddress['city'],
                customer_country_code_shipping: self.shippingAddress['country_id'],
                customer_country_shipping: self.shippingAddress['country_id'],
                customer_email: self.billingAddress['email'],
                customer_first_name_shipping: self.shippingAddress['firstname'],
                customer_id: self.getCustomerFieldsFromSession['customer_id'],
                customer_last_name_shipping: self.shippingAddress['lastname'],
                customer_state_shipping: self.shippingAddress['street'],
                customer_uid: self.getCustomerFieldsFromSession['customer_uid'],
                customer_zip_billing: self.shippingAddress['postcode'],
                customer_zip_shipping: self.billingAddress['postcode'],
                event_platform: "affirm page",
                filter_refill_end_date: "",
                free_refill_eligible: "",
                is_business_address: self.isResidential,
                page_type: "affirm page",
                page_url: window.location.href
            };


            _.each(self.getAffirmFlowItems, function (item) {
                utag_data['filter_frequency'] = item['frequency'];
                utag_data['filter_next_charge_amount'] = item['price'];
                utag_data['filter_plan_price'] = item['price'];
                utag_data['next_shipment_date'] = item['next_refill'];

                if(window.utag !== undefined){
                    window.utag.link(utag_data, function(){console.log("Affirm Payment Capture")});
                }
            });
        },

        /**
         * Tealium event for button Activate auto-refills
         * @private
         */
        _sendTealiumActivateAutoRefillsClickEvent: function() {
            let self = this;
            var utag_data = {
                tealium_event: "activate_auto_refills_click",
                event_category: "Ecommerce",
                event_action: "Clicked Activate Auto-Refills",
                customer_email: self.getCustomerFieldsFromSession['customer_email'],
                customer_uid: self.getCustomerFieldsFromSession['customer_uid'],
                event_platform: "affirm page",
                page_type: "affirm_page",
                page_url: window.location.href,
                session_id: self.getCustomerFieldsFromSession['session_id']
            };

            if(window.utag !== undefined){
                window.utag.link(utag_data, function(){console.log("Activate auto-refills")});
            }
        },

        /**
         * Adding Succes/Error message to DOM
         * @param status
         * @param message
         * @private
         */
        _showMessage: function (status, message) {
            let errorMessage = '<div class="messages js-subscription-flow-new-card-add-error-messages">' +
                '<div class="message-error message error subscription-flow-new-card-add-error-message">' +
                '<strong>Error: </strong>' + message +
                '</div></div>';

            let successMessage = '<div class="messages js-subscription-flow-new-card-add-success-messages">' +
                '<div class="message-success message success subscription-flow-new-card-add-success-message">' +
                '<strong>Success: </strong>' + message +
                '</div></div>';

            if(status === 'error') {
                $(errorMessage).insertAfter('.refills-form-wrapper .action-wrapper');
            } else if (status === 'success') {
                $(successMessage).insertAfter('.refills-form-wrapper .action-wrapper');
            }
        },

        /**
         * Event listener for credit card form
         * @private
         */
        _addEventListeners: function () {
            let self = this;
            $(document).on('changeCCField', function (e) {
                self._updateCCFieldsState(e.detail);
                self._updateSaveButtonStatus();
            });
        },

        /**
         * Toggle enable/disable button
         * @param state
         * @private
         */
        _toggleActivationButton: function (state = 'hide') {
            var button = $('.js-activate-subscription');
            button.prop('disabled', true);
            if (state === 'show') {
                button.prop('disabled', false);
            }
        },

        /**
         * Action for update the state on activation button
         * @private
         */
        _updateSaveButtonStatus: function () {
            let self = this;
            if (self._cardInputIsValid()) {
                self._toggleActivationButton('show');
            } else {
                self._toggleActivationButton();
            }
        },

        /**
         * Credit card validation
         * @return {boolean}
         * @private
         */
        _cardInputIsValid: function () {
            let self = this;
            return (self._ccFieldsCompleted.cardNumber
                && self._ccFieldsCompleted.cardExpiry
                && self._ccFieldsCompleted.cardCvc);
        },

        /**
         * Set the state for the credit card we using as validation
         * @param event
         * @private
         */
        _updateCCFieldsState: function (event) {
            let self = this;
            if (event.elementType === 'cardNumber') {
                self._ccFieldsCompleted.cardNumber = event.complete;
            } else if (event.elementType === 'cardExpiry') {
                self._ccFieldsCompleted.cardExpiry = event.complete;
            } else if (event.elementType === 'cardCvc') {
                self._ccFieldsCompleted.cardCvc = event.complete;
            }
        },

        /**
         * Copy from app/design/frontend/mlk/mlk-theme/web/js/addaddress.js
         * @param fieldId
         * @param autoCompleteObject
         * @param queryMap
         * @param savedStateField
         * @private
         */
        _initGoogleAutoComplete: function (fieldId, autoCompleteObject, queryMap, savedStateField) {
            var self = this;
            autoCompleteObject = new google.maps.places.Autocomplete(
                document.getElementById(fieldId), {types: ['geocode']});
            autoCompleteObject.setComponentRestrictions(self._getCountryRestrictionObject());
            autoCompleteObject.setFields(['address_component']);
            autoCompleteObject.addListener('place_changed', self._fillInAddress.bind(this, autoCompleteObject, queryMap, savedStateField));
            $('#' + fieldId).on('focus', function () {
                self._geolocate.bind(this, autoCompleteObject);
            });
        },

        /**
         * Copy from app/design/frontend/mlk/mlk-theme/web/js/addaddress.js
         * @param autoCompleteObject
         * @param queryMap
         * @param savedStateField
         * @private
         */
        _fillInAddress: function (autoCompleteObject, queryMap, savedStateField) {
            var widget = this;
            var place = autoCompleteObject.getPlace();

            function findByType(type) {
                return _.find(place.address_components, function (component) {
                    return _.contains(component.types, type);
                });
            }

            var streetNumber = findByType('street_number');
            var route = findByType('route');
            var streetInput = document.querySelector(queryMap['street']);
            if (streetNumber) {
                streetInput.value = streetNumber.long_name + ' ' + route.short_name + '.';
            } else if (route) {
                streetInput.value = route.short_name + '.';
            }

            var city = findByType('locality');
            if (city) {
                var cityInput = document.querySelector(queryMap['locality']);
                cityInput.value = city.long_name;
            }

            var postCode = findByType('postal_code');
            if (postCode) {
                var postCodeInput = document.querySelector(queryMap['postal_code']);
                postCodeInput.value = postCode.long_name;
            }
            _.each([streetInput, cityInput, postCodeInput], function (el) {
                if (el) {
                    var fieldNode = el.parentNode;
                    fieldNode.classList.add('fl-label-state');
                    fieldNode.classList.remove('fl-placeholder-state');
                }
            });

            var country = findByType('country');
            var regionCode = findByType('administrative_area_level_1');

            var countrySelect = document.querySelector(queryMap['country']);
            var selectedOption = countrySelect.options[countrySelect.selectedIndex];
            if (selectedOption.innerText === country.long_name) {
                var regionCodeSelect = document.querySelector(queryMap['administrative_area_level_1']);
                widget._selectByLabel(regionCodeSelect.options, regionCode.long_name);
            } else {
                savedStateField = regionCode.long_name;
                widget._selectByLabel(countrySelect.options, country.long_name);
                widget._updateStateSelectOnCountryChange(queryMap['country'], queryMap['administrative_area_level_1'], country.short_name, savedStateField);
            }
        },

        /**
         * Copy from app/design/frontend/mlk/mlk-theme/web/js/addaddress.js
         * @return {{country: [string]}}
         * @private
         */
        _getCountryRestrictionObject: function () {
            return {'country': []};
        },

        /**
         * Copy from app/design/frontend/mlk/mlk-theme/web/js/addaddress.js
         * @param optionList
         * @param label
         * @private
         */
        _selectByLabel: function (optionList, label) {
            _.each(optionList, function (option, index) {
                if (option.innerText === label) {
                    optionList.selectedIndex = index;
                }
            });
        },

        /**
         * Copy from app/design/frontend/mlk/mlk-theme/web/js/addaddress.js
         * @param countrySelector
         * @param stateSelector
         * @param countryIdOverride
         * @param savedStateField
         * @private
         */
        _updateStateSelectOnCountryChange: function (countrySelector, stateSelector, countryIdOverride = null, savedStateField = null) {
            var widget = this;
            var countryIdElement = document.querySelector(countrySelector);
            var countryId = countryIdElement.options[countryIdElement.selectedIndex].value;
            if (countryIdOverride) {
                widget._fetchRegions(countryIdOverride, stateSelector, savedStateField);
            }
            widget._fetchRegions(countryId, stateSelector);
        },

        /**
         * Copy from app/design/frontend/mlk/mlk-theme/web/js/addaddress.js
         * @param countryId
         * @param stateSelector
         * @param savedStateField
         * @private
         */
        _fetchRegions: function(countryId, stateSelector, savedStateField = null){
            var widget = this;
            var url = window.BASE_URL + "/rest/V1/directory/countries/" + countryId;
            $.ajax({
                showLoader: true,
                url: url,
                type: "GET"
            }).success(function (data) {
                widget._updateStateSelects(data.available_regions, stateSelector, savedStateField);
            }).error(function (err) {
                console.log(err);
            });
        },

        /**
         * Copy from app/design/frontend/mlk/mlk-theme/web/js/addaddress.js
         * @param regions
         * @param stateSelector
         * @param savedStateField
         * @private
         */
        _updateStateSelects: function(regions, stateSelector, savedStateField){
            var widget = this;
            var select = document.querySelector(stateSelector);
            select.innerHTML = '';

            var defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.innerText = 'State';
            select.appendChild(defaultOption);

            if(regions){
                for(var i = 0; i < regions.length; i++){
                    var option = document.createElement('option');
                    option.value = regions[i]['id'];
                    option.innerText = regions[i]['name'];
                    select.appendChild(option);
                }
            }

            if(savedStateField){
                //Break slightly out of event loop to not compete with change listener
                //TODO - revisit this, tried with flags
                widget._defer(function(){
                    widget._selectByLabel(select.options, savedStateField);
                    savedStateField = null;
                }, 300);
            } else {
                select.selectedIndex = 0;
            }
        },

        /**
         * Copy from app/design/frontend/mlk/mlk-theme/web/js/addaddress.js
         * @param func
         * @param time
         * @return {number}
         * @private
         */
        _defer: function(func, time){
            return setTimeout(func, time);
        },

        /**
         * Copy from app/design/frontend/mlk/mlk-theme/web/js/addaddress.js
         * @param autoCompleteObject
         * @private
         */
        _geolocate: function (autoCompleteObject) {
            var widget = this;
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (position) {
                    var geolocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    var circle = new google.maps.Circle(
                        {center: geolocation, radius: position.coords.accuracy});
                    autoCompleteObject.setBounds(circle.getBounds());
                });
            }
        }
    });

    return $.molekule.successPageForm;
});
