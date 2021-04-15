/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'jquery/ui',
    'mage/url',
    'mage/adminhtml/events',
    'mage/template',
    'mage/translate'
], function($, jUI, urlBuilder, varienGlobaleEvents){
    "use strict";
    
    //TODO - refactor to not use this
    if(window.customerId){
        window.subscriptionCustomerInitialSelectState = {customerId:window.customerId, 
                                                         shippingAddressId:window.shippingAddressId,
                                                         paymentCode:window.paymentCode};
    } else {
        window.subscriptionCustomerInitialSelectState = false;
    }

    const addLogging = (fn, logger = console.log) => (...args) => {
        logger(`entering ${fn.name}: ${args}`);
        try {
            const valueToReturn = fn(...args);
            logger(`exiting ${fn.name}: ${valueToReturn}`);
            return valueToReturn;
        } catch (thrownError) {
            logger(`exiting ${fn.name}: threw ${thrownError}`);
            throw thrownError;
        }
    };

    const dQS = query => document.querySelector(query);
    const dQSA = query => document.querySelectorAll(query);

    const setStyle = query => style => value => {
        const element = dQS(query);
        if (element) {
            element.style[style] = value;
        }
    }

    const setValueOnElement = query => value => {
        const element = dQS(query);
        element.value = value;
    }

    const setClickListenerByQuery = query => callback => {
        const element = dQS(query);
        element.onclick = function(){
            return callback(element);
        }
    }

    const buildOptionsOnSelect = (selectQuery, optionList) => {
        //Finds nearest select if top query is not a select
        let selectElement = dQS(selectQuery);

        if(selectElement.nodeName.toLowerCase() !== 'select'){
            selectElement = selectElement.querySelector('select');
        }
        
        selectElement.innerHTML = '';
        optionList.forEach( element => {
            const option = document.createElement("option");
            option.value = element.value;
            option.innerText = element.label;
            selectElement.appendChild(option);
        });
    }

    const selectOptionOnSelectByName = name => value => {
        const element = dQS(`select[name="${name}"]`);
        element.querySelector('option[value="' + value + '"]').selected = true;
    }

    const setTempMessage = query => time => message => {
        const element = dQS(query);
        element.innerText = message;
        setTimeout(() => element.innerText = '', time);
    }

    const wrapRed = message => `<span style="color:red">${message}</span>`;

    const hideElement = query => setStyle(query)('display')('none');
    const showElement = query => setStyle(query)('display')('block');

    const hideCustomerGrid = (_) => hideElement('#sales_order_create_customer_grid');
    const showCustomerGrid = (_) => showElement('#sales_order_create_customer_grid');

    const hideCustomerForm = (_) => hideElement('.form-inline');
    const showCustomerForm = (_) => showElement('.form-inline');

    const setCustomerId = value => $('input[name="subscriptionCustomer[customer_id]"]').val(value).change();
    const buildShippingAddressSelect = optionList => buildOptionsOnSelect('.subscription-customer-shipping-address-id', optionList);
    const buildPaymentCodeSelect = optionList => buildOptionsOnSelect('.subscription-customer-payment-code', optionList);

    const setShippingAddressOption = value => selectOptionOnSelectByName('subscriptionCustomer[shipping_address_id]')(value);
    const setPaymentCodeOption = value => selectOptionOnSelectByName('subscriptionCustomer[payment_code]')(value);

    const showMessage = message => setTempMessage('.js-subscription-customer-customer-chooser__message-box')(5000)(message);
    const showCustomerInfo = message => {
        const idBox = dQS('.js-subscription-customer-customer-chooser__current-customer-id');
        idBox.innerText = message;
    }

    const handleCustomerRowClick = event => {
        const element = Event.findElement(event, '#sales_order_create_customer_grid tr');
        if(element && element.title){
            requestCustomerInfo(element.title);
        }
    }
    
    const setSelects = returnData => {
            buildShippingAddressSelect(returnData.shippingAddresses);
            buildPaymentCodeSelect(returnData.paymentOptions);
    }

    const clearInputs = () => {
        setCustomerId('');
        const nullOption = [{value:'', label:'Please select a customer first'}];
        buildShippingAddressSelect(nullOption);
        buildPaymentCodeSelect(nullOption);
        hideCustomerForm();
        showCustomerForm();
    }

    const handleError = err => {
        showMessage(wrapRed(err));
    }

    const requestCustomerInfo = customerId => {
        const postData = {
            customerId: customerId,
            form_key: window.FORM_KEY
        }

        $.ajax({
            type: 'POST',
            url: window.backEndGetCustomerInfoUrl,
            showLoader: true,
            data: postData
        }).success(function (data) {
                if (data.Status === 'success') {
                    setCustomerId(data.customerId);
                    showCustomerInfo(data.customerId);
                    setSelects(data);
                    hideCustomerGrid();
                    showCustomerForm();
                    showElement('#grouped-product-container')
                    if(window.customerId == data.customerId){
                        const initialState = {...window.subscriptionCustomerInitialSelectState};
                        setShippingAddressOption(initialState.shippingAddressId);
                        setPaymentCodeOption(initialState.paymentCode);
                    }
                    return true;
                } else {
                    handleError(data.message);
                }
            })
            .error(function (err) {
                handleError(err)
            });
    }

    const initialize = () => {
        //TODO - move this to the template level to prevent flash on screen
        let resetCustomer = () => clearInputs();
        if(window.customerId){
            resetCustomer = () => requestCustomerInfo(window.customerId);
            hideCustomerGrid();
            showCustomerInfo(window.customerId);
        } else {
            hideCustomerForm();
        }

        hideElement('#grouped-product-container')

        //Set buttons
        setClickListenerByQuery(".js-subscription-customer-customer-chooser__show-button")(showCustomerGrid);
        setClickListenerByQuery(".js-subscription-customer-customer-chooser__hide-button")(hideCustomerGrid);
        setClickListenerByQuery(".js-subscription-customer-customer-chooser__reset-button")(resetCustomer);

        varienGlobaleEvents.attachEventHandler("gridRowClick", function(event){handleCustomerRowClick(event)})
    }

    initialize();

});

