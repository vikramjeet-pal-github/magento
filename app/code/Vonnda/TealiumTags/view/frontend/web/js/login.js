define([
    'jquery',
    'moment',
    'underscore',
    'mage/cookies',
    'Vonnda_TealiumTags/js/debug'
], function($, moment, _, tealiumDebug) {
    'use strict';
    $.widget('mage.tealiumLogin', {
        options: {

        },

        _create: function() {
            var widget = this;
            widget._initializeEventListeners();
            widget._initializeClickListeners();
        },

        _initializeEventListeners: function(){
            var widget = this;
            $(document).on('tealiumLoginFailure', function(e){
                widget._sendTealiumLoginFailureEvent();
            });
        },

        _initializeClickListeners: function(){
            var widget = this;
            $("#login-form").submit(function(e){
                widget._sendTealiumLoginClickEvent();
            });
        },

        _sendTealiumLoginClickEvent: function(){
            var widget = this;
            var utag_data = {
                tealium_event:"user_login_click",
                event_action:"Clicked Login"
            };

            if(window.utag !== undefined){
                tealiumDebug(utag_data);
                window.utag.link(utag_data, function(){console.log("Clicked login tracked.")});
            }
        },

        _sendTealiumLoginFailureEvent: function(){
            var widget = this;

            var utag_data = {
                tealium_event:"user_login_failure",
                event_action:"Login Failure",
                customer_email: $("#email").val()
            };

            if(window.utag !== undefined){
                tealiumDebug(utag_data);
                window.utag.link(utag_data, function(){console.log("Login failure tracked.")});
            }
        },

    });
    return $.mage.tealiumLogin;
});