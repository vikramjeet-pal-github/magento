define([
    'jquery',
    'underscore',
    'Vonnda_TealiumTags/js/debug'
], function($, _, tealiumDebug) {
    'use strict';
    //This could be combined with scroll
    $.widget('mage.tealiumAccountMenuClick', {
        options: {

        },

        _create: function() {
            var widget = this;
            widget._initializeClickListeners();
        },

        _initializeClickListeners: function(){
            var widget = this;
            $("a.edit-profile").on('click', function(e){
                widget._sendTealiumAccountMenuClick(e);
            });
        },

        _sendTealiumAccountMenuClick: function(e){
            var widget = this;
            var utag_data = {
                event_action:"Clicked Account Menu",
                event_category:"Ecommerce",
                tealium_event:"account_menu_click"
            };

            if(window.utag !== undefined){
                tealiumDebug(utag_data);
                window.utag.link(utag_data, function(){console.log("Account menu click tracked.")});
            }
        },

    });
    return $.mage.tealiumAccountMenuClick;
});