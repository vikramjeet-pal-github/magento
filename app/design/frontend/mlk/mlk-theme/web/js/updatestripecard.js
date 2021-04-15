define([
    'jquery',
    'mage/validation',
    'Magento_Ui/js/modal/confirm',
    'mage/translate',
    'underscore',
    'https://maps.googleapis.com/maps/api/js?key=' + window.googleAutocompleteApiKey + '&libraries=places'
], function($, $validation, $confirm, $t, _) {
    'use strict';

    $.widget('mage.updateStripeCard', {
        options: {},

        _create: function() {
            var widget= this;
            widget._ccFieldsCompleted = {
                cardNumber:false,
                cardExpiry:false,
                cardCvc:false
            };
            widget._addressComplete = false;

            //Google Maps
            widget._placesSearch;
            widget._newBillingAddressAutocomplete;
            widget._savedNewBillingAddressState = null;

            widget._newBillingAddressFormQueryMap = {
                street: '#billing-streetOne',
                locality: '#billing-city',
                administrative_area_level_1:'.subscription-new-billing-address #state',
                country:'#billing-country',
                postal_code: '#billing-postcode'
            };

            widget._initGoogleAutoComplete(
                'billing-streetOne',
                widget._newBillingAddressAutocomplete,
                widget._newBillingAddressFormQueryMap,
                widget._savedNewBillingAddressState
            );

            widget._overrideStripeSave();
            widget._initializeButtons();
            widget._addEventListeners();
        },

        _initializeButtons: function(){
            var widget= this;
            widget._simpleFormValidation(
                '.subscription-new-billing-address form',
                 widget._handleSimpleFormValidationSuccess.bind(this),
                 widget._handleSimpleFormValidationFailure.bind(this)
            );

            $(document).on('change','#billing-country',function() {
                if(!widget._savedNewBillingAddressState){
                    widget._updateStateSelectOnCountryChange('#billing-country', '.subscription-new-billing-address #state');
                }
            });
        },

        _addEventListeners: function(){
            var widget= this;
            $(document).on('changeCCField', function(e){
                widget._updateCCFieldsState(e.detail);
                widget._updateSaveButtonStatus();
            });

            $(document).on('openAddressAndPaymentNewPayment', function(e){
                stripe.initStripeElements();
                stripe.clearCardErrors();
                widget._ccFieldsCompleted = {
                    cardNumber:false,
                    cardExpiry:false,
                    cardCvc:false
                };
                widget._addressComplete = false;
                widget._clearForm('.subscription-new-billing-address form', ['billing-country']);
                $('subscription-list__billing-address-save-button').prop('disabled', true).removeClass('active');
            });
        },

        _selectByLabel: function(optionList, label){
            _.each(optionList, function(option, index){
                if(option.innerText === label){
                    optionList.selectedIndex = index;
                }
            });
        },

        _setAddressCompleteTrue: function(){
            var widget = this;
            widget._addressComplete = true;
        },

        _setAddressCompleteFalse: function(){
            var widget = this;
            widget._addressComplete = false;
        },

        _handleSimpleFormValidationSuccess: function(){
            var widget = this;
            widget._setAddressCompleteTrue();
            widget._updateSaveButtonStatus();
        },

        _handleSimpleFormValidationFailure: function(){
            var widget = this;
            widget._setAddressCompleteFalse();
            widget._updateSaveButtonStatus();
        },

        _updateCCFieldsState: function(event){
            var widget = this;
            if(event.elementType === 'cardNumber'){
                widget._ccFieldsCompleted.cardNumber = event.complete;
            } else if(event.elementType === 'cardExpiry'){
                widget._ccFieldsCompleted.cardExpiry = event.complete;
            } else if(event.elementType === 'cardCvc'){
                widget._ccFieldsCompleted.cardCvc = event.complete;
            }
        },

        _cardInputIsValid: function(){
            var widget = this;
            return (widget._ccFieldsCompleted.cardNumber && widget._ccFieldsCompleted.cardExpiry && widget._ccFieldsCompleted.cardCvc);
        },

        _disableSaveButton: function() {
            var saveButton = document.querySelector('.js-stripe-payments-save-card');
            saveButton.classList.remove('active');
            saveButton.disabled = true;
        },

        _enableSaveButton: function() {
            var saveButton = document.querySelector('.js-stripe-payments-save-card');
            saveButton.classList.add('active');
            saveButton.disabled = false;
        },

        _updateSaveButtonStatus: function(){
            var widget = this;
            if (widget._addressComplete && widget._cardInputIsValid()) {
                widget._enableSaveButton();
            } else {
                widget._disableSaveButton();
            }
        },

        _clearForm: function(query, selectIgnoreIds = []){
            var form = document.querySelector(query);

            var inputs = form.querySelectorAll('input');
            for(var i = 0; i < inputs.length; i++){
                if (inputs[i].type == 'hidden') { // to skip the hidden email field, which is needed if customer has no stripe profile
                    continue;
                }
                inputs[i].value = "";
                var fieldNode = inputs[i].parentNode;
                fieldNode.classList.remove('fl-label-state');
                fieldNode.classList.add('fl-placeholder-state');
            }

            var checkboxes = form.querySelectorAll('checkbox');
            for(var i = 0; i < checkboxes.length; i++){
                checkboxes[i].checked = false;
            }

            var selects = form.querySelectorAll('select');
            for(var i = 0; i < selects.length; i++){
                if(_.includes(selectIgnoreIds, selects[i].id)){
                    continue;
                }
                selects[i].selectedIndex = 0;
            }

        },

        _showErrorMessage: function(message){
            $confirm({
                title: "Error adding card",
                content: message,
                actions: {
                    /** @inheritdoc */
                    confirm: function () {
                        //TODO
                    }
                }
            });
        },

        _showSuccessMessage: function(message){
            return; //Default message being shown for now
            $confirm({
                title: "Success adding card",
                content: message,
                actions: {
                    /** @inheritdoc */
                    confirm: function () {
                        //TODO
                    }
                }
            });
        },

        _addParagraph: function(element, content){
            var paragraph = document.createElement("div");
            paragraph.innerText = content;
            element.appendChild(paragraph);
        },

        //Because toggle/hide methods were being inconsistent
        _showElement: function(query, style = "block"){
            var element = document.querySelector(query);
            if (element) {
                element.style.display = style;
            }
        },

        _hideElement: function(query){
            var element = document.querySelector(query);
            if (element) {
                element.style.display = "none";
            }
        },

        //Billing
        _overrideStripeSave: function() {
            var widget = this;
            $(document.body).on('click', '.js-stripe-payments-save-card', function(event){
                event.preventDefault();
                if ($('.subscription-new-billing-address form').validation('isValid') && $('input#billing-telephone').val().length > 13) {
                    widget._captureBillingAddressFields();
                    //return stripe.UpdateSaveCard(this, function() {
                        $.ajax({
                            type: 'POST',
                            showLoader: true,
                            url: window.BASE_URL + 'subscription/customer/updatecard',
                            data: {
                                 form_key: $('.js-add-card-formkey').val(),
                                 firstname: $('#billing-firstname').val(),
                                 lastname: $('#billing-lastname').val(),
                                 Address1: $('#billing-streetOne').val(),
                                 Address2: $('#billing-streetTwo').val(),
                                 city:     $('#billing-city').val(),
                                 region:   $('#states').find(':selected').text(),                            
                                postalcode:$('#billing-postcode').val(),
                                country:   $('#billing-country :selected').text(),
                                telephone: $('#billing-telephone').val(),
                                payment: {cc_stripejs_token: stripe.token},
                                
                            }
                        }).success(function(data) {
                            if (data.status === 'error') {
                                widget._showErrorMessage(data.error);
                                return;
                            }
                            widget._handleAddPaymentSuccess(data);
                        }).error(function(err) {
                            console.log(err);
                            widget._showErrorMessage('There was an error, please try again');
                        });
                 //   });
                } else {
                    $('input#billing-telephone').addClass('mage-error');
                    $('<div class="mage-error" generated="true">Please enter valid phone number.</div>').insertAfter('input#billing-telephone');
                }
            });
        },

        _captureBillingAddressFields: function() {
            var street = [
                $('#billing-streetOne').val()
            ];
            if ($('#billing-streetTwo').val() != '') {
                street.push($('#billing-streetTwo').val());
            }
            stripe.quote = {
                guestEmail: $('#customer-email').val(),
                billingAddress: function() {
                    return {
                        firstname: $('#billing-firstname').val(),
                        lastname: $('#billing-lastname').val(),
                        street: street,
                        city: $('#billing-city').val(),
                        region: $('.subscription-new-billing-address #state').find('option:selected').text(),
                        postcode: $('#billing-postcode').val(),
                        countryId: $('#billing-country').find('option:selected').val(),
                        telephone: $('#billing-telephone').val()
                    }
                }
            };
        },

        _handleAddPaymentSuccess: function(data){
            var widget = this;
            stripe.initStripeElements();
            stripe.clearCardErrors();
            widget._clearForm('.subscription-new-billing-address form', ['billing-country']);
            widget._addPaymentToList(data);
            $('.subscription-address-block, .subscription-payment-block, .subscription-billing .subscription-list__new-address').toggleClass('hide');
            // widget._showSuccessMessage("Card added");
        },
        
        _addPaymentToList: function(data){
            var payment = $('<div class="subscription-list__billing-address-edit-list-item js-payment-list-item" data-stripecustomerid="'+data.stripe_customer_id+'" data-expirationdate="'+data.expiration_date+'" data-paymentcode="'+data.payment_code+'">\
                <p class="subscription-list__billing-address-edit-list-card"><span class="cc-last4">'+ data.card_string +'</span> <span class="cc-exp">exp. '+data.expiration_date+'</span></p>\
                <a class="action delete js-card-delete" href="#" role="delete-card" data-paymentcode="'+data.payment_code+'"><span>'+$t('Delete Card')+'</span></a>\
            </div>');
            $('.js-payment-container').prepend(payment);
        },

        //TODO - Break into a own file 
        //This just checks to see if required values are present, to enable/disable button, mage validation shows errors
        _simpleFormValidation: function(query, successCallback, failureCallback){
            var widget = this;
            var form = document.querySelector(query);
            var formFields = form.querySelectorAll('input');

            function checkFields(){
                if(form.querySelector('.js-address-field').value == ''){
                    failureCallback();
                } else {
                    successCallback();
                }
            }

            for(var i = 0; i < formFields.length; i++){
                formFields[i].addEventListener('keydown', function(){
                    checkFields();
                });

                formFields[i].addEventListener('blur', function(){
                    checkFields();
                });
            }

            var selectFields = form.querySelectorAll('select');
            for(var i = 0; i < selectFields.length; i++){
                selectFields[i].addEventListener('change', function(){
                    checkFields();
                });
            }
        },

        _checkRequiredFields:function(form){
           var formFields = form.querySelectorAll('input');
           for(var i = 0; i < formFields.length; i++){
                var validateByDataField = formFields[i].getAttribute('data-validate') === "{required:true}";
                if(validateByDataField && formFields[i].value == ''){
                    return false;
                }
            }

            var selectFields = form.querySelectorAll('select');
            for(var i = 0; i < selectFields.length; i++){
                var validateByDataField = selectFields[i].getAttribute('data-validate') === "{'validate-select':true}";
                var validateByClass = selectFields[i].classList.contains("required-entry");
                var shouldValidate = validateByDataField || validateByClass;
                if(shouldValidate && selectFields[i].options[selectFields[i].selectedIndex].value == ''){
                    return false;
                }
            }

            return true;
        },

        //Fetching Country on Change
        //Country Selects
        _updateStateSelectOnCountryChange: function(countrySelector, stateSelector, countryIdOverride = null, savedStateField = null){
            var widget = this;
            var countryIdElement = document.querySelector(countrySelector);
            var countryId= countryIdElement.options[countryIdElement.selectedIndex].value;
            if(countryIdOverride){
                widget._fetchRegions(countryIdOverride, stateSelector, savedStateField);
            }
            widget._fetchRegions(countryId, stateSelector);
        },

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

        _defer: function(func, time){
            return setTimeout(func, time);
        },

        //Google AutoComplete
        _fillInAddress: function(autoCompleteObject, queryMap, savedStateField) {
            var widget = this;
            var place = autoCompleteObject.getPlace();
            function findByType(type){
                return _.find(place.address_components, function(component){
                    return _.contains(component.types,type);
                });
            }

            var streetNumber = findByType('street_number');
            var route = findByType('route');
            var streetInput = document.querySelector(queryMap['street']);
            if(streetNumber){
                streetInput.value = streetNumber.long_name + ' ' + route.short_name + '.';
            } else {
                streetInput.value = route.short_name + '.';
            }
            
            var city = findByType('locality');
            var cityInput = document.querySelector(queryMap['locality']);
            cityInput.value = city.long_name;

            var postCode = findByType('postal_code');
            var postCodeInput = document.querySelector(queryMap['postal_code']);
            postCodeInput.value = postCode.long_name;

            _.each([streetInput, cityInput, postCodeInput], function(el){
                var fieldNode = el.parentNode;
                fieldNode.classList.add('fl-label-state');
                fieldNode.classList.remove('fl-placeholder-state');
            });

            var country = findByType('country');
            var regionCode = findByType('administrative_area_level_1');
            
            var countrySelect = document.querySelector(queryMap['country']);
            var selectedOption = countrySelect.options[countrySelect.selectedIndex];
            if(selectedOption.innerText === country.long_name){
                var regionCodeSelect = document.querySelector(queryMap['administrative_area_level_1']);
                widget._selectByLabel(regionCodeSelect.options, regionCode.long_name);
            } else {
                savedStateField = regionCode.long_name;
                widget._selectByLabel(countrySelect.options, country.long_name);
                widget._updateStateSelectOnCountryChange(queryMap['country'], queryMap['administrative_area_level_1'], country.short_name, savedStateField);
            }
        },

        _initGoogleAutoComplete: function(fieldId, autoCompleteObject, queryMap, savedStateField){
            var widget = this;
            autoCompleteObject = new google.maps.places.Autocomplete(
                document.getElementById(fieldId), {types: ['geocode']});
            autoCompleteObject.setComponentRestrictions(widget._getCountryRestrictionObject());
            autoCompleteObject.setFields(['address_component']);
            autoCompleteObject.addListener('place_changed', widget._fillInAddress.bind(this, autoCompleteObject, queryMap, savedStateField));
            $('#' + fieldId).on('focus', function(){
                widget._geolocate.bind(this, autoCompleteObject);
            });
        },

        _getCountryRestrictionObject: function(){
            return {'country' : [window.storeCountryCode]};
        },

        _geolocate: function(autoCompleteObject){
            var widget= this;
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
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
    return $.mage.updateStripeCard;
});