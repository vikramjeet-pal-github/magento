define([
    'jquery',
    'moment',
    'underscore',
    'Vonnda_TealiumTags/js/debug'
], function($, moment, _, tealiumDebug) {
    'use strict';
    $.widget('mage.tealiumCheckoutEvents', {

        _create: function() {
            var widget = this;
            widget._isCustomerLoggedIn = window.isCustomerLoggedIn;
            widget._checkoutData= _.extend({},JSON.parse(window.tealiumCheckoutData));
            widget._customerEmail = null;
            widget._customerShippingAddress = null;
            widget._emailPreferences = "";
            widget._isBusinessAddress = "";

            widget._initializeEventListeners();

            if(widget._checkForExistingCustomer()){
                widget._customerEmail = window.checkoutConfig.customerData.email;
                widget._sendTealiumCustomerInfoStepExistingCustomer();
            }
        },

        _initializeEventListeners: function(){
            var widget = this;

            $(document).on('tealiumEventCustomerInfoStepNewCustomer', function(event, email){
                widget._customerEmail = email;
                widget._sendTealiumCustomerInfoStepNewCustomer(email);
            });

            $(document).on('tealiumEventShippingStep', function(event, address){
                if(address.id){
                    widget._setExistingShippingAddressInUtagFormat(address);
                } else {
                    widget._setShippingAddressInUtagFormat(address);
                }
                widget._sendTealiumShippingStep(address);
            });

            $(document).on('tealiumEventPaymentStep', function(event, shippingMethod){
                widget._sendTealiumPaymentStep(shippingMethod);
            });
        },

        _checkForExistingCustomer: function(){
            var widget = this;
            return widget._isCustomerLoggedIn;
        },

        _setShippingAddressInUtagFormat: function(addressData){
            var widget = this;
            var utagAddress = {
                customer_address_1_shipping:addressData.street,
                customer_address_2_shipping:"",
                customer_zip_shipping:addressData.postcode,
                customer_city_shipping:addressData.city,
                customer_state_shipping:addressData.region,
                customer_country_shipping:addressData.country_id === 'US' ? 'United States' : 'Canada',
                customer_country_code_shipping:addressData.country_id,
                customer_first_name_shipping:addressData.firstname,
                customer_last_name_shipping:addressData.lastname,
                is_business_address:addressData['custom_attributes[residential]'] == 'false' ? "Yes" : "No"
            }

            widget._customerShippingAddress = utagAddress;
        },

        _setExistingShippingAddressInUtagFormat: function(addressData){
            var widget = this;
            var utagAddress = {
                customer_address_1_shipping:addressData.street[0],
                customer_address_2_shipping:addressData.street[1],
                customer_zip_shipping:addressData.postcode,
                customer_city_shipping:addressData.city,
                customer_state_shipping:addressData.region.region,
                customer_country_shipping:addressData.country_id === 'US' ? 'United States' : 'Canada',
                customer_country_code_shipping:addressData.country_id,
                customer_first_name_shipping:addressData.firstname,
                customer_last_name_shipping:addressData.lastname,
                is_business_address:""
            }

            widget._customerShippingAddress = utagAddress;
        },

        //Customer Info Step
        _subscriberIsChecked: function(){
            var widget = this;
            var checkbox = document.querySelector('div[data-role="newsletter-subscriber"] input');
            if(checkbox){
                return checkbox.checked;
            } else {
                return false;
            }
        },

        _sendTealiumCustomerInfoStepExistingCustomer: function(){
            var widget = this;
            var customerInfoTagData = {
                tealium_event: "checkout",
                event_category: "Ecommerce",
                event_action: "Customer Info Step",
                checkout_step: "2"
            };

            var checkoutData = _.extend({}, widget._checkoutData);
            
            widget._emailPreferences = widget._checkoutData["email_preferences"];

            var tagData = _.extend(checkoutData, customerInfoTagData);

            if(window.utag !== undefined){
                tealiumDebug(tagData);
                window.utag.view(tagData, function(){console.log("Customer info step tracked.")});
            }
        },

        _sendTealiumCustomerInfoStepNewCustomer: function(email){
            var widget = this;
            widget._emailPreferences = widget._subscriberIsChecked() ? true : false;
            var customerInfoTagData = {
                tealium_event: "checkout",
                event_category: "Ecommerce",
                event_action: "Customer Info Step",
                checkout_step: "2",
                customer_email: email,
                email_preferences: widget._emailPreferences
            };

            var checkoutData = _.extend({}, widget._checkoutData);

            var tagData = _.extend(checkoutData, customerInfoTagData);

            if(window.utag !== undefined){
                tealiumDebug(tagData);
                window.utag.view(tagData, function(){console.log("Customer info step tracked.")});
            }
        },

        //Shipping Method Step
        _sendTealiumShippingStep: function(address){
            var widget = this;
            widget._isBusinessAddress = address.is_business_address;
            var shippingTagData = {
                tealium_event: "checkout",
                event_category: "Ecommerce",
                event_action: "Shipping Method Step",
                checkout_step: "3",
                customer_email: widget._customerEmail,
                email_preferences: widget._emailPreferences
            };

            var checkoutData = _.extend({}, widget._checkoutData);

            var tagData = _.extend(checkoutData, shippingTagData, widget._customerShippingAddress);

            if(window.utag !== undefined){
                tealiumDebug(tagData);
                window.utag.view(tagData, function(){console.log("Shipping step tracked.")});
            }
        },

        //Payment Info Step
        _sendTealiumPaymentStep: function(shippingMethod){
            var widget = this;
            var paymentTagData = {
                tealium_event: "checkout",
                event_category: "Ecommerce",
                event_action: "Payment Info Step",
                checkout_step: "4",
                customer_email: widget._customerEmail,
                email_preferences: widget._emailPreferences,
                is_business_address: widget._isBusinessAddress
            };

            var checkoutData = _.extend({}, widget._checkoutData);
            
            var tagData = _.extend(checkoutData, paymentTagData, shippingMethod, widget._customerShippingAddress);

            if(window.utag !== undefined){
                tealiumDebug(tagData);
                window.utag.view(tagData, function(){console.log("Payment step tracked.")});
            }
        },


    });
    return $.mage.tealiumCheckoutEvents;
});