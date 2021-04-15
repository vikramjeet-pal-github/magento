define([
    'jquery',
    'moment',
    'underscore',
    'Vonnda_TealiumTags/js/debug'
], function($, moment, _, tealiumDebug) {
    'use strict';
    $.widget('mage.tealiumProfile', {
        options: {

        },

        _create: function() {
            var widget = this;
            widget._initializeClickListeners();
        },

        _initializeClickListeners: function(){
            var widget = this;
            $("#change-email").on('click', function(e){
                var changeEmail = document.querySelector("#change-email");
                if(changeEmail.checked){
                    widget._sendTealiumChangeEmailClickEvent();
                }
            });

            $("#change-password").on('click', function(e){
                var changePassword = document.querySelector("#change-password");
                if(changePassword.checked){
                    widget._sendTealiumChangePasswordClickEvent();
                }

            });
        },

        _sendTealiumChangeEmailClickEvent: function(){
            var widget = this;
            var utag_data = {
                tealium_event:"change_email_click",
                event_action:"Clicked Change Email"
            };

            if(window.utag !== undefined){
                tealiumDebug(utag_data);
                window.utag.link(utag_data, function(){console.log("Clicked change e-mail tracked.")});
            }
        },

        _sendTealiumChangePasswordClickEvent: function(){
            var widget = this;

            var utag_data = {
                tealium_event:"change_password_click",
                event_action:"Clicked Change Password"
            };

            if(window.utag !== undefined){
                tealiumDebug(utag_data);
                window.utag.link(utag_data, function(){console.log("Click changed password tracked.")});
            }
        },

    });
    return $.mage.tealiumProfile;
});