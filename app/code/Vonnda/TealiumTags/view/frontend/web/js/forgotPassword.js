define([
    'jquery',
    'moment',
    'underscore',
    'Vonnda_TealiumTags/js/debug'
], function($, moment, _, tealiumDebug) {
    'use strict';
    $.widget('mage.tealiumForgotPassword', {
        options: {

        },

        _create: function() {
            var widget = this;
            widget._initializeEventListeners();
            widget._initializeClickListeners();
        },

        _initializeEventListeners: function(){
            var widget = this;
        },

        _initializeClickListeners: function(){
            var widget = this;
            $("#form-validate").submit(function(e){
                widget._sendTealiumForgotPasswordClickEvent();
            });
        },

        _sendTealiumForgotPasswordClickEvent: function(){
            var widget = this;
            var utag_data = {
                tealium_event:"forgot_password_click_sign_in",
                event_action:"Clicked Forgot Password"
            };

            if(window.utag !== undefined){
                tealiumDebug(utag_data);
                window.utag.link(utag_data, function(){console.log("Clicked forgot password tracked.")});
            }
        },

    });
    return $.mage.tealiumForgotPassword;
});