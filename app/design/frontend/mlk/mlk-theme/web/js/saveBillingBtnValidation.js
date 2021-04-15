define([
    'jquery'
], function ($, _) {
    'use strict';
    $.widget('mage.saveBillingBtnValidation', {
        options: {
        },
        _create: function() {
            var _self = this;
            var btn = _self.element;
            var parentBlock = _self.element.closest('.onestep-billing-address').find('form.form');
            var input = parentBlock.find('input[aria-required=true]');

            $(document.body).on('keyup keypress change blur', input, function() {
                //console.log(_self._validateShippingBtn());
                if (_self._validateButton()) { 
                    btn.removeClass('inactive');
                } else {
                    btn.addClass('inactive');
                }
            });

        },
        _validateButton: function () {
            var isValid = false;
            $.each($('.onestep-billing-address form.form').find('input:visible'), function () {
                //console.log($(this));
                if ((!$(this).val() && $(this).attr('aria-required') == 'true') || $('.onestep-billing-address .field._error').length > 0) {
                    isValid = false;
                } else {
                    isValid = true;
                }
            });
            return isValid;
        }

    });
    return $.mage.saveBillingBtnValidation;
});