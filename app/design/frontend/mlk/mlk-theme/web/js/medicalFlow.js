define([
    'jquery'
], function ($, _) {
    'use strict';
    $.widget('mage.medicalFlow', {
        options: {
        },
        _create: function() {
            var _self = this;
            _self.selectMedical();
            _self.selectBusiness();
            if(_self.isGiftOrder()){
                _self.handleGiftOrder();
            }
        },

        isGiftOrder: function() {
            return window.checkoutConfig.quoteData.gift_order == "1";
        },

        selectMedical: function() {
            $('#is_medical').on('change', function(){
                var _this = $(this);
                var business = $('#is_business');
                var originalBusiness = $('input[name="custom_attributes[is_residential]"]');
                if ($(this).prop('checked')){
                    business.prop('checked', false);
                    originalBusiness.prop('checked', true).trigger('change');
                    business.trigger('change');
                    $('input[name="company"]').val('MedicalPurchase').trigger('change');
                    $('input[name="company"]').closest('div.field').hide();
                } else if(business.prop('checked') == false) {
                    $('input[name="company"]').val('');
                    originalBusiness.prop('checked', false).trigger('change');
                }
            });
        },

        selectBusiness: function() {
            var medical = $('#is_medical');
            var business = $('#is_business');
            var originalBusiness = $('input[name="custom_attributes[is_residential]"]');
            business.on('change', function(){
                if ($(this).prop('checked')){
                    medical.prop('checked', false);
                    originalBusiness.prop('checked', true).trigger('change');
                    $('input[name="company"]').val('').trigger('change');
                    $('input[name="company"]').attr('maxlength', '83');
                    $('input[name="company"]').closest('div.field').show();
                } else{
                    $('input[name="company"]').closest('div.field').hide();
                }
            });
        },

        handleGiftOrder: function() {
            var medicalNotice = document.querySelector('.medicalflow-notice');
            medicalNotice.classList.add("hide");

            var medicalField = document.querySelector('.medical-option-medical');
            medicalField.classList.add("hide");
            
            var businessText = document.querySelector('.medical-option-business label');
            businessText.innerHTML = "This order is going to a business.";
        }
        

    });
    return $.mage.medicalFlow;
});
