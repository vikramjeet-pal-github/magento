define([
    'jquery',
    'moment',
    'underscore',
    'Vonnda_TealiumTags/js/debug'
], function($, moment, _, tealiumDebug) {
    'use strict';
    $.widget('mage.tealiumAccountPortal', {
        options: {

        },

        _create: function() {
            var widget = this;
            widget._initializeEventListeners();
            widget._sendPasswordResetSignInEvent();
        },

        _initializeEventListeners: function(){
            var widget = this;
            $(document).off('activateSubscriptionClick').on('activateSubscriptionClick', function(e, subscription){
                widget._sendTealiumActivateSubscriptionClick(subscription);
            });

            $(document).off('activateSubscriptionSuccess').on('activateSubscriptionSuccess', function(e, subscription, data){
                widget._sendTealiumActivateSubscriptionSuccess(subscription, data);
            });

            $(document).on('activateSubscriptionFailure', function(e, subscription, err){
                if(!subscription) return;
                widget._sendTealiumActivateSubscriptionFailure(subscription, err);
            });
			
			//renewal chnage from frontend
			$(document).off('subscriptionChangeDateClick').on('subscriptionChangeDateClick', function(e, subscription, data){
                widget._sendTealiumSubscriptionChangeDateClick(subscription, data);
            });
			$(document).off('subscriptionChangeDateSuccess').on('subscriptionChangeDateSuccess', function(e, subscription, data){
                widget._sendTealiumSubscriptionChangeDateSuccess(subscription, data);
            });
			$(document).off('subscriptionChangeDateFailure').on('subscriptionChangeDateFailure', function(e, subscription, err){
                widget._sendTealiumSubscriptionChangeDateFailure(subscription, err);
            });
			//renewal chnage from frontend

            $(document).on('updatePaymentClick', function(e, subscription){
                widget._sendTealiumUpdatePaymentClick(subscription);
            });

            $(document).on('updatePaymentSuccess', function(e, subscription){
                widget._sendTealiumUpdatePaymentSuccess(subscription);
            });

            $(document).on('updatePaymentFailure', function(e, subscription, err){
                widget._sendTealiumUpdatePaymentFailure(subscription, err);
            });

            $(document).on('tealiumAutoRenewOff', function(e, subscription, data){
                if(!subscription) return;
                widget._sendTealiumAutoRenewOff(subscription, data);
            });
        },

        _sendTealiumActivateSubscriptionClick: function(subscription){
            var widget = this;
            var utag_data = {
                event_action:"Clicked Activate Auto-Refills",
                event_category:"Ecommerce",
                tealium_event:"activate_auto_refills_click",
                activation_location:"Account",
                activate_location:"Account"
            };
            widget._setTestGroup(utag_data);
            widget._setCustomerData(utag_data);
            widget._setSubscriptionAddressFields(utag_data, subscription);
            widget._setSubscriptionDeviceFields(utag_data, subscription);
            widget._setSubscriptionSuccessFields(utag_data, subscription);
            widget._setCartInfo(utag_data);

            if(window.utag !== undefined){
                tealiumDebug(utag_data);
                window.utag.link(utag_data, function(){console.log("Activate subscription click tracked.")});
            }
        },

        _sendTealiumActivateSubscriptionSuccess: function(subscription, data){
            var widget = this;
            var utag_data = {
                event_action:"Activated Auto-Refills",
                event_category:"Ecommerce",
                tealium_event:"activate_auto_refills_success",
                activation_location:"Account"
            };
            widget._setTestGroup(utag_data);
            widget._setCustomerData(utag_data);
            widget._setSubscriptionAddressFields(utag_data, subscription);
            
            var subscriptions = widget._updateSubscriptionList(data, subscription);
            widget._setDeviceFieldsAllSubscriptions(utag_data, subscriptions)
            widget._setSubscriptionFieldsAllSubscriptions(utag_data, subscriptions);

            widget._setCartInfo(utag_data);

            if(window.utag !== undefined){
                tealiumDebug(utag_data);
                window.utag.link(utag_data, function(){console.log("Activate subscription success tracked.")});
            }
        },

        _sendTealiumActivateSubscriptionFailure: function(subscription, err){
            var widget = this;
            var utag_data = {
                event_action:"Activated Auto-Refills Failed",
                event_category:"Ecommerce",
                tealium_event:"activate_auto_refills_failure"
            };
            widget._setTestGroup(utag_data);
            widget._setCustomerData(utag_data);
            widget._setSubscriptionAddressFields(utag_data, subscription);
            widget._setSubscriptionDeviceFields(utag_data, subscription);
            widget._setSubscriptionFailureFields(utag_data, subscription, err);
            widget._setCartInfo(utag_data);

            if(window.utag !== undefined){
                tealiumDebug(utag_data);
                window.utag.link(utag_data, function(){console.log("Activate subscription failure tracked.")});
            }
        },
		//renewal tealium event
		_sendTealiumSubscriptionChangeDateClick: function(subscription, data){
            var widget = this;
            var utag_data = {
                event_action:"Changed Subscription Date",
                event_category:"Account",
                tealium_event:"subscription_change_date_click",
                event_platform:"Account",
				page_url:window.BASE_URL + "subscription/customer/autorefill",
                page_type:"account",
				'subscription_id' : data['subscription_id'],
				'filter_refill_end_date_previous': data['previous_next_renewal_date'],
				'filter_refill_end_date': data['new_next_renewal_date']
            };
            widget._setCustomerData(utag_data);
            widget._setSubscriptionAddressFields(utag_data, subscription);
            widget._setSubscriptionDeviceFields(utag_data, subscription);

            if(window.utag !== undefined){
				//console.log(utag_data);
                tealiumDebug(utag_data);
                window.utag.link(utag_data, function(){console.log("Changed subscription date click tracked.")});
            }
        },

        _sendTealiumSubscriptionChangeDateSuccess: function(subscription, data){
            var widget = this;
            var utag_data = {
                event_action:"Changed Subscription Date",
                event_category:"Account",
                tealium_event:"subscription_change_date",
                event_platform:"Account",
				page_url:window.BASE_URL + "subscription/customer/autorefill",
                page_type:"account",
				'subscription_id' : data['subscription_id'],
				'filter_refill_end_date_previous': data['previous_next_renewal_date'],
				'filter_refill_end_date': data['new_next_renewal_date']
            };
            widget._setCustomerData(utag_data);
            widget._setSubscriptionAddressFields(utag_data, subscription);
            widget._setSubscriptionDeviceFields(utag_data, subscription);


            if(window.utag !== undefined){
                tealiumDebug(utag_data);
                window.utag.link(utag_data, function(){console.log("Changed subscription date success tracked.")});
            }
        },

        _sendTealiumSubscriptionChangeDateFailure: function(subscription, data){
            var widget = this;
             var utag_data = {
                event_action:"Turned off Auto-Renew",
                event_category:"Account",
                tealium_event:"subscription_change_date_fail",
                event_platform:"Account",
				page_url:window.BASE_URL + "subscription/customer/autorefill",
                page_type:"account",
				'subscription_id' : data['subscription_id'],
				'filter_refill_end_date_previous': data['previous_next_renewal_date'],
				'filter_refill_end_date': data['new_next_renewal_date'],
				'error_message': data['error']
            };
            widget._setCustomerData(utag_data);
            widget._setSubscriptionAddressFields(utag_data, subscription);
            widget._setSubscriptionDeviceFields(utag_data, subscription);

            if(window.utag !== undefined){
                tealiumDebug(utag_data);
                window.utag.link(utag_data, function(){console.log("Changed subscription date failure tracked.")});
            }
           
        },
		//renewal tealium event
        _sendTealiumAutoRenewOff: function(subscription, data){
            var widget = this;
            var utag_data = {
                event_action:"Turned off Auto-Renew",
                event_category:"Account",
                tealium_event:"auto_renew_off",
                event_label:subscription.cancel_reason,
                activation_location:"Account",
                page_url:window.BASE_URL + "subscription/customer/autorefill",
                page_type:"account"
            };

            widget._setTestGroup(utag_data);
            widget._setCustomerData(utag_data);

            var subscriptions = widget._updateSubscriptionList(data, subscription);
            widget._setDeviceFieldsAllSubscriptions(utag_data, subscriptions)
            widget._setSubscriptionFieldsAllSubscriptions(utag_data, subscriptions);
            
            widget._setCartInfo(utag_data);

            if(window.utag !== undefined){
                tealiumDebug(utag_data);
                window.utag.link(utag_data, function(){console.log("Turn off subscription tracked.")});
            }
        },

        _sendTealiumUpdatePaymentClick: function(subscription){
            var widget = this;
            var utag_data = {
                event_action:"Clicked Update Payment",
                event_category:"Ecommerce",
                tealium_event:"update_payment_click",
                activation_location:"Account",
                activate_location:"Account"
            };
            widget._setTestGroup(utag_data);
            widget._setCustomerData(utag_data);
            widget._setSubscriptionAddressFields(utag_data, subscription);
            widget._setSubscriptionDeviceFields(utag_data, subscription);
            widget._setSubscriptionSuccessFields(utag_data, subscription);
            widget._setCartInfo(utag_data);

            if(window.utag !== undefined){
                tealiumDebug(utag_data);
                window.utag.link(utag_data, function(){console.log("Update payment click tracked.")});
            }
        },

        _sendTealiumUpdatePaymentSuccess: function(subscription){
            var widget = this;
            var utag_data = {
                event_action:"Update Payment Success",
                event_category:"Ecommerce",
                tealium_event:"update_payment_success",
                activation_location:"Account"
            };
            widget._setTestGroup(utag_data);
            widget._setCustomerData(utag_data);
            widget._setSubscriptionAddressFields(utag_data, subscription);
            widget._setSubscriptionDeviceFields(utag_data, subscription);
            widget._setSubscriptionSuccessFields(utag_data, subscription);
            widget._setCartInfo(utag_data);

            if(window.utag !== undefined){
                tealiumDebug(utag_data);
                window.utag.link(utag_data, function(){console.log("Update payment success tracked.")});
            }
        },

        _sendTealiumUpdatePaymentFailure: function(subscription, err){
            var widget = this;
            var utag_data = {
                event_action:"Update Payment Failure",
                event_category:"Ecommerce",
                tealium_event:"update_payment_failure"
            };
            widget._setTestGroup(utag_data);
            widget._setCustomerData(utag_data);
            if(subscription){
                widget._setSubscriptionAddressFields(utag_data, subscription);
                widget._setSubscriptionDeviceFields(utag_data, subscription);
            }
            widget._setSubscriptionFailureFields(utag_data, subscription, err);
            widget._setCartInfo(utag_data);

            if(window.utag !== undefined){
                tealiumDebug(utag_data);
                window.utag.link(utag_data, function(){console.log("Update payment failure tracked.")});
            }
        },

        _sendPasswordResetSignInEvent: function(e){
            var widget = this;
            if($.cookie('passwordResetSuccess') !== "true"){
                return;
            }

            $.cookie("passwordResetSuccess", "", { path: '/'});

            var utag_data = {
                event_action:"Reset Password - Sign in Success",
                event_category:"Ecommerce",
                tealium_event:"reset_password_success_sign_in"
            };

            if(window.utag !== undefined){
                tealiumDebug(utag_data);
                window.utag.link(utag_data, function(){console.log("Reset password success sign in tracked")});
            }
        },

        //Utility
        _setTestGroup: function(dataObject){
            var widget = this;
            dataObject['ab_test_group'] = "";

            return dataObject;
        },

        _setCustomerData: function(dataObject){
            var widget = this;
            dataObject['customer_email'] = window.customerEmail ? window.customerEmail : "";
            dataObject['customer_id'] = window.customerId ? window.customerId.toString() : "";
            dataObject['session_id'] = window.customerSessionId ? window.customerSessionId : "";
            dataObject['customer_uid'] = window.customerUid ? window.customerUid : "";

            return dataObject;
        },

        _setSubscriptionAddressFields: function(dataObject, subscription){
            var widget = this;
            if(!subscription || !subscription.shipping_address){
                return dataObject;
            }
            var country = widget._getCountryFromCode(subscription);
            var streetOne = subscription.shipping_address.street[0];
            var streetTwo = subscription.shipping_address.street[1] ? 
                            subscription.shipping_address.street[1] : '';

            var subscriptionFields = {
                customer_address_1_shipping:streetOne,
                customer_address_2_shipping:streetTwo,
                customer_zip_shipping:subscription.shipping_address.postcode,
                customer_city_shipping:subscription.shipping_address.city,
                customer_state_shipping:subscription.shipping_address.region.region_code,
                customer_country_shipping:country,
                customer_country_code_shipping:subscription.shipping_address.country_id,
                customer_first_name_shipping:subscription.shipping_address.firstname,
                customer_last_name_shipping:subscription.shipping_address.lastname,
            };

            _.extend(dataObject, subscriptionFields);

            return dataObject;
        },

        _setSubscriptionDeviceFields: function(dataObject, subscription){
            var widget = this;
            var deviceName = widget._getDeviceNameFromDevice(subscription);

            var subscriptionFields = {
                device_id:subscription.device.entity_id,
                device_name:"",
                serial_number:[subscription.device.serial_number]
            };

            _.extend(dataObject, subscriptionFields);

            return dataObject;
        },

        _setDeviceFieldsAllSubscriptions: function(dataObject, subscriptions){
            var widget = this;
            var deviceFields = {
                device_id:[],
                device_name:[],
                serial_number:[]
            }

            _.each(subscriptions, function(subscription){
                var deviceName = widget._getDeviceNameFromDevice(subscription);
                deviceFields.device_id.push(subscription.device.entity_id);
                deviceFields.device_name.push("");
                deviceFields.serial_number.push(subscription.device.serial_number);
                
            }, deviceFields);

            _.extend(dataObject, deviceFields);

            return dataObject;
        },

        _setSubscriptionSuccessFields: function(dataObject, subscription){
            var widget = this;
            var paymentOnFile = subscription.payment &&
                subscription.payment.status === 'valid';
            var autoRefillOn = subscription.status === 'autorenew_on' ? true : false;
            var freeRefillEligible = subscription.subscription_plan.number_of_free_shipments ? true : false;
            var freeRefillCharge = subscription.subscription_plan.payment_required_for_free ? true : false;

            var subscriptionFields = {
                subscription_id:[subscription.id.toString()],
                filter_refill_end_date:[subscription.next_order],
                payment_on_file:paymentOnFile ? true : false,
                auto_refill:[autoRefillOn],
                free_refill_eligible:freeRefillEligible,
                filter_refill_charge:freeRefillCharge,
                filter_frequency:[subscription.subscription_plan.frequency],
                filter_plan_price:[subscription.subscription_plan.plan_price.toString()]
            };

            _.extend(dataObject, subscriptionFields);

            return dataObject;
        },

        _setSubscriptionFieldsAllSubscriptions: function(dataObject, subscriptions){
            var widget = this;
    
            var subscriptionFields = {
                subscription_id:[],
                filter_refill_end_date:[],
                payment_on_file:[],
                auto_refill:[],
                free_refill_eligible:[],
                filter_refill_charge:[],
                filter_frequency:[],
                filter_plan_price:[]
            };

            _.each(subscriptions, function(subscription){
                var paymentOnFile = subscription.payment &&
                    subscription.payment.status === 'valid';
                var autoRefillOn = subscription.status === 'autorenew_on' ? true : false;
                var freeRefillEligible = subscription.subscription_plan.number_of_free_shipments ? true : false;
                var freeRefillCharge = subscription.subscription_plan.payment_required_for_free ? true : false;

                subscriptionFields.subscription_id.push(subscription.id.toString());
                subscriptionFields.filter_refill_end_date.push(subscription.next_order);
                subscriptionFields.payment_on_file.push(paymentOnFile ? true : false);
                subscriptionFields.auto_refill.push(autoRefillOn);
                subscriptionFields.free_refill_eligible.push(freeRefillEligible);
                subscriptionFields.filter_refill_charge.push(freeRefillCharge);
                subscriptionFields.filter_frequency.push(subscription.subscription_plan.frequency);
                subscriptionFields.filter_plan_price.push(subscription.subscription_plan.plan_price.toString())
            }, subscriptionFields);

            _.extend(dataObject, subscriptionFields);

            return dataObject;
        },

        _setSubscriptionFailureFields: function(dataObject, subscription, err){
            var widget = this;
            var message = "";
            if(err.responseJSON){
                var message = err.responseJSON.message;
            }

            var subscriptionFields = {
                error_message:message,
                error_label:message,
            };

            _.extend(dataObject, subscriptionFields);

            return dataObject;
        },

        //This can fetch if it becomes necessary
        _getCountryFromCode: function(subscription){
            var countryMap = {
                US:"United States",
                CA:"Canada"
            };

            if(subscription.shipping_address && subscription.shipping_address.country_id){
                var country = countryMap[subscription.shipping_address.country_id];
                if(country){ return country;}
            }

            return "";
        },

        _getDeviceNameFromDevice: function(subscription){
            var device = subscription.device;
            if(!device){return ""};

            if(device.associated_product_name){
                return device.associated_product_name;
            } else if(device.sku){
                return device.sku;
            } else {
                return "";
            }
        },

        _setCartInfo: function(dataObject){
            var widget = this;
            return _.extend(dataObject, window.tealiumCartInfo);
        },

        _updateSubscriptionList: function(subscriptions, subscription){
            for(var i = 0; i < subscriptions.length; i++){
                if(subscriptions[i]['id'] == subscription.id){
                    subscriptions[i] =  subscription;
                    break;
                }
            }

            return _.extend({}, subscriptions);
        }

    });
    return $.mage.tealiumAccountPortal;
});