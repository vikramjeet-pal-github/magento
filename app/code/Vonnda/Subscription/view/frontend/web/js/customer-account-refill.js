define([
    "jquery",
    'jquery/ui',
    'underscore'
], function ($, jUI, _) {
    "use strict";

    initButtons();
    initEventListeners();
    scrollToSubscriptionOnLoad();

    //Alias
    //TODO - this needs to be a widget
    function dQS(query){return document.querySelector(query);}
    function dQSA(query){return document.querySelectorAll(query);}

    function getUrlParameter(name) {
        name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
        var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
        var results = regex.exec(location.search);
        return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
    };

    function clearForm(query, selectIgnoreIds = []){
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

    };
    
    function initButtons()
    {
        var shippingAddressEditButtons = dQSA(".subscription-list__shipping-address-edit-button");
        for(var i = 0; i < shippingAddressEditButtons.length; i++){
            shippingAddressEditButtons[i].onclick = function(){
                var subscriptionId = event.target.getAttribute('data-id');
                var addressId = event.target.getAttribute('data-addressid');
                var deviceName = event.target.getAttribute('data-devicename');
                hideSubscriptionList();
                showShippingAddressEdit(subscriptionId, addressId, deviceName);
                window.scrollTo(0, 300);
            }
        }

        //The id in the following functions refers to the subscription_customer_id
        var shippingAddressSaveButton = dQS(".subscription-list__shipping-address-save-button");
        shippingAddressSaveButton.onclick = function(){
            var subscriptionId = event.target.getAttribute('data-id');
            var customerId = event.target.getAttribute('data-customerId');
            var dataForm = $(this).closest('div.subscription-list__new-address').find('form.subscription-list__shipping-address-form');
            if (dataForm.validation('isValid')) {
                submitShippingAddressSave(subscriptionId, customerId);
            }
        }

        var shippingAddressChooseButton = dQS(".subscription-list__shipping-address-choose-button");
        shippingAddressChooseButton.onclick = function(){
            var subscriptionId = event.target.getAttribute('data-id');
            var addressId = event.target.getAttribute('data-addressId');
            submitShippingAddressChoose(subscriptionId, addressId);
        }

        var shippingAddressOptions = dQSA(".subscription-list__shipping-address-edit-list-item");
        for(var i = 0; i < shippingAddressOptions.length; i++){
            shippingAddressOptions[i].onclick = function(){
                handleClickAddressItem(event);
            }
        }

        var shippingAddressEditRedirect = dQS(".subscription-list__shipping-address-edit-redirect-button");
        shippingAddressEditRedirect.onclick = function(){
            var addressId = event.target.getAttribute('data-addressId');
            window.location.replace(window.addressBookUrl + 'edit/id/' + addressId);
        }

        simpleFormValidation('.subscription-list__shipping-address form', 
                             enableNewShippingAddressButton, 
                             disableNewShippingAddressButton);
    }

    function initEventListeners()
    {
        $(document).on('changeAddressText', function(e, subscriptionId, addressId, street){
            changeAddressText(subscriptionId, addressId, street);
        });
    }

    function scrollToSubscriptionOnLoad()
    {
        if(getUrlParameter('subscription')){
            var subscriptionId = getUrlParameter('subscription');
            scrollToSubscription(subscriptionId);
        }
    }

    function scrollToSubscription(subscriptionId)
    {
        //TODO - this is not working
        $('html, body').animate({
            scrollTop: $('.subscription-list__subscription-container[data-id="' + subscriptionId+ '"]').offset().top
        }, 200);
    }

    function setShippingAddressButtons(subscriptionId, addressId)
    {
        var shippingAddressSaveButton = dQS(".subscription-list__shipping-address-save-button");
        shippingAddressSaveButton.setAttribute('data-id', subscriptionId);
        var shippingAddressChooseButton = dQS(".subscription-list__shipping-address-choose-button");
        shippingAddressChooseButton.setAttribute('data-id', subscriptionId);
        var shippingAddressEditRedirectButton = dQS(".subscription-list__shipping-address-edit-redirect-button");
        shippingAddressEditRedirectButton.setAttribute('data-id', subscriptionId);
        shippingAddressEditRedirectButton.setAttribute('data-addressid', addressId);
    }

    function showSubscriptionList()
    {
        var subscriptionListContainer = dQS('.subscription-list__list-container');
        subscriptionListContainer.style.display = "block";
    }

    function hideSubscriptionList()
    {
        var subscriptionListContainer = dQS('.subscription-list__list-container');
        subscriptionListContainer.style.display = "none";
    }

    function showShippingAddressEdit(subscriptionCustomerId, addressId, deviceName)
    {
        var shippingAddressEditContainer = dQS('.subscription-list__shipping-address');
        shippingAddressEditContainer.style.display = "block";
        var deviceNameElement = dQS('.subscription-list__shipping-address-devicename'); 
        deviceNameElement.innerText = deviceName;
        setShippingAddressButtons(subscriptionCustomerId, addressId);
    }

    function hideShippingAddressEdit()
    {
        var shippingAddressEditContainer = dQS('.subscription-list__shipping-address');
        shippingAddressEditContainer.style.display = "none";
    }

    function setAddressIdOnChooseShipping(addressId)
    {
        var shippingAddressChooseButton = dQS(".subscription-list__shipping-address-choose-button");
        shippingAddressChooseButton.setAttribute('data-addressId', addressId);
    }

    function clearShippingAddressChooser()
    {
        var shippingAddressOptions = dQSA(".subscription-list__shipping-address-edit-list-item");
        for(var i = 0; i < shippingAddressOptions.length; i++){
            shippingAddressOptions[i].classList.remove('active');
        }
        setAddressIdOnChooseShipping('');
    }

    function showErrorMessage(message){
        //TODO - need a better indication
        console.log(message);
    }

    function resetTabs(){
        clearForm('.js-existing-subscription-new-address-form', ['shipping-country_id']);
        $('.subscription-list__address-edit-list').removeClass('hide');
        $('.subscription-list__new-address').addClass('hide');
        $('.subscription-list__shipping-address-add').removeClass('active');
        hideShippingAddressEdit();
        showSubscriptionList();
        clearShippingAddressChooser();
    }

    function handleClickAddressItem(event){
        var addressId = event.currentTarget.getAttribute('data-addressId');
        if(_.contains(event.currentTarget.classList, 'active')){
            clearShippingAddressChooser();
            disableNewShippingChooseButton();
            return setAddressIdOnChooseShipping('');
        }
        clearShippingAddressChooser();
        event.currentTarget.classList.add('active');
        
        setAddressIdOnChooseShipping(addressId);
        enableNewShippingChooseButton();
    }

    //Form submits
    //TODO - refactor to use service interface
    function submitShippingAddressSave(subscriptionCustomerId, customerId)
    {
        var addressFields = {};
        var formFields = dQSA('.subscription-list__shipping-address-form-input');

        for(var i = 0; i < formFields.length; i++){
            var name = formFields[i].getAttribute('name');
            var value = formFields[i].value;
            addressFields[name] = value;
        }

        var countryIdElement = document.querySelector('#shipping-country_id');
        var regionIdElement = document.querySelector('.subscription-list__shipping-address-form').querySelector('#state');

        var countryId= countryIdElement.options[countryIdElement.selectedIndex].value;
        var regionId = regionIdElement.options[regionIdElement.selectedIndex].value;

        addressFields['country_id'] = countryId;
        addressFields['region_id'] = regionId;

        var url = window.baseUrl + "subscription/customer/saveshippingaddress";
        var request = $.ajax( {
            type:'POST',
            url:url,
            showLoader: true,
            data:{
                  addressFields:addressFields,
                  subscriptionCustomerId:subscriptionCustomerId,
                  customerId:customerId
                }})
            .success(function(data) {
                console.log(data);
                handleSaveAddressSuccess(data);
                resetTabs();
            })
            .error(function(err) {
                console.log(err);
                showErrorMessage("There was an error saving the shipping address, try again later");
            });
    }
    
    function submitShippingAddressChoose(subscriptionCustomerId, addressId)
    {
        var url = window.baseUrl + "subscription/customer/switchshippingaddress";
        var request = $.ajax( {
            type:'POST',
            url:url,
            showLoader: true,
            data:{subscriptionCustomerId:subscriptionCustomerId, addressId:addressId}})
            .success(function(data) {
                handleChooseAddressSuccess(data);
            })
            .error(function(err) {
                console.log(err);
                console.log("There was an error, try again later");
            });
    }

    function handleSaveAddressSuccess(data)
    {
        changeAddressText(data.subscriptionCustomerId, data.addressId, data.street);
        addShippingAddressToList(data.addressId, data.address);
        showUpdateMessage(data.subscriptionCustomerId, 'Thanks for the update.');
        resetTabs();
    }

    function handleChooseAddressSuccess(data)
    {
        changeAddressText(data.subscriptionCustomerId, data.addressId, data.street);
        showUpdateMessage(data.subscriptionCustomerId, 'Thanks for the update.');
        scrollToSubscription(data.subscriptionCustomerId);
        resetTabs();
    }

    function changeAddressText(subscriptionId, addressId, street)
    {
        var subscriptionAddress = dQS('.subscription-list__address[data-subscriptionid="' + subscriptionId + '"]');
        var addressParagraph = subscriptionAddress.querySelector('p');
        var button = addressParagraph.querySelector('button');
        button.setAttribute('data-addressid', addressId);
        addressParagraph.innerHTML = street + button.outerHTML;

        //Reset Click Listener, seems to wipe it
        var button = addressParagraph.querySelector('button');
        button.onclick = function(){
            var subscriptionId = event.target.getAttribute('data-id');
            var addressId = event.target.getAttribute('data-addressid');
            var deviceName = event.target.getAttribute('data-devicename');
            hideSubscriptionList();
            showShippingAddressEdit(subscriptionId, addressId, deviceName);
        }
    }

    function addShippingAddressToList(addressId, addressFields){
        var listContainer = document.querySelector('.subscription-list__address-edit-list');
        var listItem = document.createElement("div");
        listItem.classList.add('subscription-list__shipping-address-edit-list-item');
        listItem.setAttribute('data-addressid', addressId);

        listItem.onclick = function(e){
            handleClickAddressItem(e);
        }
        
        var name = addressFields.firstname + " " + addressFields.lastname;
        var street = addressFields.streetOne;
        var cityAndState = addressFields.city + ", " + addressFields.regionCode + " " + addressFields.postcode;
        addParagraph(listItem, name);
        addParagraph(listItem, street);
        addParagraph(listItem, cityAndState);
        addParagraph(listItem, addressFields.telephone);

        listContainer.insertBefore(listItem, listContainer.firstChild);
        $(listItem).toggleClass('active');
    }

    function addParagraph(element, content){
        var paragraph = document.createElement("p");
        paragraph.innerText = content;
        element.appendChild(paragraph);
    }

    function showUpdateMessage(subscriptionId, message)
    {
        var messageContainer = dQS('.subscription-list__thank-you-message[data-subscriptionid="' + subscriptionId + '"]');
        messageContainer.innerText = message;
        messageContainer.classList.remove('hide');
    }

    //Change Country Select
    $(document).on('change','#shipping-country_id',function() {
        $(document).trigger('changeEditAddressExistingSubStateSelect');
        var countryIdElement = document.querySelector('#shipping-country_id');
        var countryId= countryIdElement.options[countryIdElement.selectedIndex].value;
        fetchRegions(countryId);
    });

    function fetchRegions(countryId){
        var url = window.BASE_URL + "/rest/V1/directory/countries/" + countryId;
        $.ajax({
            showLoader: true,
            url: url,
            type: "GET"
        }).success(function (data) {
            updateStateSelects(data.available_regions);
        }).error(function (err) {
            console.log(err);
        });
    }

    function updateStateSelects(regions){
        var select = document.querySelector('.subscription-list__shipping-address-form #state');
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

        select.selectedIndex = 0;
    }

    //TODO - Break into a own file 
    //This just checks to see if required values are present, to enable/disable button, mage validation shows errors
    function simpleFormValidation(query, successCallback, failureCallback){
        var form = document.querySelector(query);
        var formFields = form.querySelectorAll('input');

        function checkFields(){
            //var allRequiredFieldsHaveInput = checkRequiredFields(form);
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
        }

        var selectFields = form.querySelectorAll('select');
        for(var i = 0; i < selectFields.length; i++){
            selectFields[i].addEventListener('change', function(){
                checkFields();
            });
        }
    }

    function checkRequiredFields(form){
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
    }

    function enableNewShippingAddressButton(){
        var saveButton = document.querySelector('.subscription-list__shipping-address-save-button');
        saveButton.classList.add('active');
        saveButton.disabled = false;
    }

    function disableNewShippingAddressButton(){
        var saveButton = document.querySelector('.subscription-list__shipping-address-save-button');
        saveButton.classList.remove('active');
        saveButton.disabled = true;
    }

    function enableNewShippingChooseButton(){
        var saveButton = document.querySelector('.subscription-list__shipping-address-choose-button');
        saveButton.classList.add('active');
        saveButton.disabled = false;
    }

    function disableNewShippingChooseButton(){
        var saveButton = document.querySelector('.subscription-list__shipping-address-choose-button');
        saveButton.classList.remove('active');
        saveButton.disabled = true;
    }
});