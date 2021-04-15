define([
    'jquery',
    'Magento_Customer/js/address',
    'Magento_Ui/js/modal/confirm',
    'mage/validation',
    'underscore',
    'https://maps.googleapis.com/maps/api/js?key='
            + window.googleAutocompleteApiKey + '&libraries=places'
], function($, $address, $confirm,validator, _) {
    'use strict';
    $.widget('mage.addAddress', {
        options: {
            domain : '',
            customerid: ''
        },

        _create: function() {
            var widget = this;

            //Google Maps
            widget._placesSearch;
            widget._newAddressAutocomplete;
            widget._savedNewAddressState = null;

            widget._newAddressFormQueryMap = {
                street: '#shipping-streetOne',
                locality: '#shipping-city',
                administrative_area_level_1:'.subscription-list__new-address #state',
                country:'.subscription-list__new-address #country',
                postal_code: '#shipping-postcode'
            }

            widget._initGoogleAutoComplete('shipping-streetOne',
                                           widget._newAddressAutocomplete, 
                                           widget._newAddressFormQueryMap, 
                                           widget._savedNewAddressState);

            widget._initializeButtons();
            widget._initializeEventListeners();
        },

        _initializeButtons: function(){
            var widget = this;
            var newAddressButton = document.querySelector('.subscription-list__shipping-address-choose-button');
            newAddressButton.onclick = function(){
                event.preventDefault();
                var customerId = this.getAttribute("data-customerid");
                var dataForm = $(this).closest('.subscription-list__new-address').find('form.subscription-list__shipping-address-form');
                if (dataForm.validation('isValid') && $('input#shipping-telephone').val().length > 13) {
                    widget._addNewAddress(customerId);
                } else {
                    $('input#shipping-telephone').addClass('mage-error');
                    $('<div class="mage-error" generated="true">Please enter valid phone number.</div>').insertAfter('input#shipping-telephone');
                }
            }

            $(document).on('change','#country',function() {
                if(!widget._savedNewAddressState){
                    widget._updateStateSelectOnCountryChange('#country', '.subscription-list__new-address #state');
                }
            });
        },

        _initializeEventListeners: function(){
            var widget = this;
            $(document).on('openAddressAndPaymentNewAddress', function(){
                $('.subscription-list__shipping-address-choose-button').prop('disabled', true).removeClass('active');
                widget._clearForm(".subscription-list__shipping-address-form", ['country']);
            });

            widget._simpleFormValidation('.subscription-list__shipping-address-form', 
                                         widget._enableSaveButton, 
                                         widget._disableSaveButton);
        },

        _selectByLabel: function(optionList, label){
            _.each(optionList, function(option, index){
                if(option.innerText === label){
                    optionList.selectedIndex = index;
                }
            });
        },

        _disableSaveButton: function(){
            var saveButton = document.querySelector('.subscription-list__shipping-address-choose-button');
            saveButton.classList.remove('active');
            saveButton.disabled = true;
        },

        _enableSaveButton: function(){
            var saveButton = document.querySelector('.subscription-list__shipping-address-choose-button');
            saveButton.classList.add('active');
            saveButton.disabled = false;
        },

        _clearForm: function(query, selectIgnoreIds = []){
            var form = document.querySelector(query);

            var inputs = form.querySelectorAll('input');
            for(var i = 0; i < inputs.length; i++){
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
            console.log(message);
            $confirm({
                title: "Error adding address",
                content: '',
                actions: {
                    /** @inheritdoc */
                    confirm: function () {
                        //TODO
                    }
                }
            });
        },

        _showSuccessMessage: function(message){
            return;//Right now default magento message is being shown
            $confirm({
                title: "Address added",
                content: 'You have successfully added an address',
                actions: {
                    /** @inheritdoc */
                    confirm: function () {
                        //TODO
                    }
                }
            });
        },

        _addNewAddress: function (customerId) {
            var widget = this;
            var domainName = widget.options.domain;
            var getUrl = domainName + 'subscription/customer/addcustomeraddress';
            var addressFields = {};
            var formFields = $('.subscription-list__shipping-address-form-input');

            for(var i = 0; i < formFields.length; i++){
                var name = formFields[i].getAttribute('name');
                var value = formFields[i].value;
                addressFields[name] = value;
            }
            addressFields['country_id'] = $('select#country').val();
            addressFields['region_id'] = $('select#state').val();

            $.ajax( {
                showLoader: true,
                type:'POST',
                url:getUrl,
                data: {
                    addressFields:addressFields,
                    customerId: customerId
                }
            })
                .success(function(data) {
                    widget._handleAddAddressSuccess(data);
                })
                .error(function(err) {
                    console.log(err);
                    widget._showErrorMessage("There was a problem adding the address, please try again");
                }).
                done(function(){
                });
        },

        _handleAddAddressSuccess: function(data){
            var widget = this;
            widget._clearForm(".subscription-list__shipping-address-form", ['shipping-country_id']);
            widget._addAddressToList(data.address.id,data.address);
            $('.subscription-address-block, .subscription-payment-block, .subscription-address .subscription-list__new-address').toggleClass('hide');
            widget._showSuccessMessage("Address added successfully");
        },

        _addAddressToList: function(addressId, addressFields){
            var widget = this;
            var addressBlock = document.querySelector('.subscription-address-block');
            var addressContentBlock = addressBlock.querySelector('.content-block');
            var listItem = document.createElement("div");
            listItem.classList.add('subscription-address-item');
            listItem.setAttribute('data-addressid', addressId);
            var name = addressFields.firstname + " " + addressFields.lastname;
            var street = addressFields.streetOne;
            var streetTwo = addressFields.streetTwo;
            var cityAndState = addressFields.city + ", " + addressFields.regioncode + " " + addressFields.postcode;
            widget._addParagraph(listItem, name);
            widget._addParagraph(listItem, street);
            if(streetTwo !== ""){
                widget._addParagraph(listItem, streetTwo);
            }
            widget._addParagraph(listItem, cityAndState);
            widget._addParagraph(listItem, addressFields.telephone);

            listItem.firstChild.style['font-weight'] = 700;

            widget._addDeleteButton(listItem, addressId);

            addressContentBlock.insertBefore(listItem, addressContentBlock.firstChild);
        },

        _addParagraph: function(element, content){
            var paragraph = document.createElement("div");
            paragraph.innerText = content;
            element.appendChild(paragraph);
        },

        // <a class="action delete" href="#" role="delete-address" data-addressid="<?= $shippingAddress->getId() ?>"><span><?= $block->escapeHtml(__('Delete Address')) ?></span></a>
        _getDeleteButtonHtml: function(addressId){
                return "<a class=\"action delete\" href=\"#\" role=\"delete-address\" data-addressid=\"" + addressId + "\"><span>Delete Address</span></a>";
        },

        _addDeleteButton:  function(element, addressId){
            var deleteElement = this._getDeleteButtonHtml(addressId);
            $(element).append(deleteElement);
            element.onclick = function(){
                $address._deleteAddress();
            }
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
            } else if (route) {
                streetInput.value = route.short_name + '.';
            }

            var city = findByType('locality');
            if(city) {
                var cityInput = document.querySelector(queryMap['locality']);
                cityInput.value = city.long_name;
            }

            var postCode = findByType('postal_code');
            if(postCode) {
                var postCodeInput = document.querySelector(queryMap['postal_code']);
                postCodeInput.value = postCode.long_name;
            }
            _.each([streetInput, cityInput, postCodeInput], function(el){
                if(el) {
                    var fieldNode = el.parentNode;
                    fieldNode.classList.add('fl-label-state');
                    fieldNode.classList.remove('fl-placeholder-state');
                }
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
    return $.mage.addAddress;
});