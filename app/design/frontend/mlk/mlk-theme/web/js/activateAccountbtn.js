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
    $.widget('mage.activateAccountBtn', {
        options: {
        },
        _create: function() {
            console.log('activate account button widget loaded');
            this._activateButton();
            this._tabCreateClicked();
        },

        _activateButton: function () {
            var _self = this;
            var btn = _self.element.find('.checkout-next-step');
            var parentBlock = _self.element;
            var input = parentBlock.find('input[aria-required="true"]');
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
            return this._validateAccount();
        },

        _validateAccount: function () {
            var loginForm = $('form[data-role="email-with-possible-login"]');
            if (window.checkoutConfig.passwordRequired) {
                var isValid = true;
                $.each(loginForm.find('input'), function () {
                    if (!$(this).val().trim() && $(this).attr('aria-required') == 'true') {
                        isValid = false;
                    } else {
                        isValid = true;
                    }
                });
                //console.log(isValid && $('input[data-role=checkout-agreements-input').prop('checked'));
                return isValid && $('input[data-role=checkout-agreements-input]').is(':checked');

            } else {
                var isValid = true;
                $.each(loginForm.find('input'), function () {
                    if (!$(this).val().trim() && $(this).attr('aria-required') == 'true') {
                        isValid = false;
                    } else {
                        isValid = true;
                    }
                });
                if( $('input[data-role=checkout-agreements-input]').length ){
                    return isValid && $('input[data-role=checkout-agreements-input]').is(':checked') && $('input#customer-email').val();
                } else {
                    return isValid && $('input#customer-email').val();
                }
                
            }

        },

        _tabCreateClicked: function() {
            var _self = this;
            $('.step-1-tab.tab-create, .tab-sign-in.step-1-tab').on('click', function(){
                _self._activateButton();
            });
        }

    });
    return $.mage.activateAccountBtn;
});
