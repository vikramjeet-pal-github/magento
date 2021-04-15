define([
    'jquery'
], function ($, _) {
    'use strict';
    $.widget('mage.MlkBtnActive', {
        options: {
            inputOnChange: '',
            buttonSelector: ''
        },
        _create: function() {
            this._activateButton();

        },

        _activateButton: function () {
            var _self = this;
            var triggerer = _self.element.find(_self.options.inputOnChange);
            var button = _self.element.find(_self.options.buttonSelector);
            triggerer.on('change paste keyup', function(){
                button.removeAttr('disabled');
            });
        }

    });
    return $.mage.MlkBtnActive;
});