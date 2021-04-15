define([
    'jquery',
    'underscore',
    'Vonnda_TealiumTags/js/debug'
], function($, _, tealiumDebug) {
    'use strict';
    $.widget('mage.tealiumScroll', {
        options: {

        },

        _create: function() {
            var widget = this;
            widget._hasScrolled25 = false;
            widget._hasScrolled50 = false;
            widget._hasScrolled75 = false;
            widget._hasScrolled100 = false;
            widget._initializeEventListeners();
        },

        _initializeEventListeners: function(){
            var widget = this;
            $(document).on('scroll', function(e){
                var scroll = $(window).scrollTop();
                var docHeight = $(document).height();
                var winHeight = $(window).height();

                var scrollPercent = (scroll / (docHeight - winHeight)) * 100;

                if(!widget._hasScrolled25 && scrollPercent > 25){
                    widget._sendTealiumScrollEvent("25");
                    widget._hasScrolled25 = true;
                } else if(!widget._hasScrolled50 && scrollPercent > 50){
                    widget._sendTealiumScrollEvent("50");
                    widget._hasScrolled50 = true;
                } else if(!widget._hasScrolled75 && scrollPercent > 75){
                    widget._sendTealiumScrollEvent("75");
                    widget._hasScrolled75 = true;
                } else if(!widget._hasScrolled100 && scrollPercent > 99){
                    widget._sendTealiumScrollEvent("100");
                    widget._hasScrolled100 = true;
                }
            });
        },

        _sendTealiumScrollEvent: function(percent){
            var widget = this;
            var url = window.location.href;
            var utag_data = {
                tealium_event:"scroll_tracking",
                event_action:"Scrolled[" + url + "]",
                scroll_percent:percent

            };

            if(window.utag !== undefined){
                tealiumDebug(utag_data);
                window.utag.link(utag_data, function(){console.log("Scroll tracked.")});
            }
        },
    });
    return $.mage.tealiumScroll;
});