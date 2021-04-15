define([
    'jquery',
    'Magento_Ui/js/form/element/abstract',
    'ko'
], function ($, Abstract, ko) {
    'use strict';

    ko.bindingHandlers.autoResize = {
        init: function(element, valueAccessor, allBindings, viewModel, bindingContext) {
            var copy = $(element).clone();
            var maximumLength = 200;
            $(document.body).append(copy);
            var scrollHeight = copy[0].scrollHeight;
            copy.remove();
            ko.computed(function() {
                ko.unwrap(valueAccessor());
                if($(element).val() === ''){
                    $(element).removeAttr('style');
                    $(element.previousElementSibling).removeAttr('style');
                } else {
                    element.style.height = '1px';
                    element.style.height = element.scrollHeight +'px';
                    element.previousElementSibling.style.transform = 'translateY(-' + (element.scrollHeight > 48 ? element.scrollHeight/2 : 24) +'px)'
                }
                var remainingCharacters = maximumLength - element.value.length;      
                $('.rchars').html(remainingCharacters + ' char.');
            });
            if($(element).val() === ''){
                $(element).removeAttr('style');
                $(element.previousElementSibling).removeAttr('style');
            } else {
                element.style.height = scrollHeight +'px';
                element.previousElementSibling.style.transform = 'translateY(-' + (scrollHeight > 48 ? scrollHeight/2 : 24) +'px)'
            }
            
        }
    };

    return Abstract.extend({
        defaults: {
            cols: 15,
            rows: 1,
            elementTmpl: 'ui/form/element/textarea',
            message: ''
        },
        initObservable: function () {
            let message = "";
            if(window.checkoutConfig.giftMessage 
                && window.checkoutConfig.giftMessage.orderLevel){
                    message = window.checkoutConfig.giftMessage.orderLevel.message
            }
            this._super().observe({
                'message': message
            });
            return this;
        }
    });
});
