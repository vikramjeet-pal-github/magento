define([
    'jquery'
], function ($, _) {
    'use strict';
    $.widget('mage.DiscountActive', {
        options: {
            inputOnChange: ''
        },
        _create: function() {
            this._activateButton();

        },

        _activateButton: function () {
            var _self = this;
            var triggerer = _self.element.closest('form').find(_self.options.inputOnChange);
            var button = _self.element;
            triggerer.on('change paste keyup', function(){
                button.removeAttr('disabled');
            });
        }

    });
    return $.mage.DiscountActive;
});