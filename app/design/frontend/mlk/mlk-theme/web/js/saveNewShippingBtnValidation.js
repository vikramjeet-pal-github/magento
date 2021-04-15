define([
    'jquery'
], function ($, _) {
    'use strict';
    $.widget('mage.saveNewShippingBtnValidation', {
        options: {
        },
        _create: function() {
            var _self = this;
            var btn = _self.element;
            var parentBlock = _self.element.closest('.new-shipping-address-form').find('form.form');
            var input = parentBlock.find('input[aria-required=true]');


            $(document.body).on('keyup keypress change blur', input, function() {
                if (_self._validateButton() && parentBlock.find('input[name="telephone"]').val().length > 13) {
                    btn.removeClass('inactive');
                } else {
                    btn.addClass('inactive');
                }
            });

            $('.new-shipping-address-form .title').on('click', function(){
                if(($(this).closest('.new-shipping-address-form').hasClass('active')) && _self._validateButton()){
                    btn.removeClass('inactive');
                }
            });

        },
        _validateButton: function () {
            var isValid = false;
            $.each($('.new-shipping-address-form.active form.form').find('input:visible'), function () {
                if ((!$(this).val() && $(this).attr('aria-required') == 'true') || $('.new-shipping-address-form.active .field._error').length > 0) {
                    isValid = false;
                } else {
                    isValid = true;
                }
            });
            return isValid;
        }

    });
    return $.mage.saveNewShippingBtnValidation;
});
