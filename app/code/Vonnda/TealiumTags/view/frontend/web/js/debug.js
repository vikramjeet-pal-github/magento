define([
    'jquery',
    'moment',
    'underscore'
], function($, moment, _) {
    'use strict';
    
    return function(utag_data){
        var enabled = false;
        //TODO - add to config in BE
        var debugEnabled = window.tealiumJsDebugEnabled;
        if(enabled || debugEnabled){
            console.log("Tealium Event - " + utag_data['tealium_event']);
            console.log(utag_data);
        }
    }
});