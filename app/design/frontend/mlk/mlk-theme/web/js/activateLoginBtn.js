define([
    'jquery',
    'underscore',
    'uiRegistry',
], function(
    $,
    _,
    registry,
) {
    'use strict';
    $.widget('mage.activateLoginBtn', {
        options: {
        },
        _create: function() {
            this._activateButton();

        },

        _activateButton: function () {
            var _self = this;
            var btn = _self.element.find('button.action-login');
            var parentBlock = _self.element;
            var input = parentBlock.find('input.input-text');
            //console.log(input);
            $(document.body).on('keyup keypress change blur', input, function() {
                if (_self._validateStep()) {
                    btn.removeClass('inactive');
                } else {
                    btn.addClass('inactive');
                }
            });

        },

        _validateStep: function () {
            return $('#login-email').val() && $('#login-password').val();
        },

       

    });
    return $.mage.activateLoginBtn;
});