define([
    'jquery'
], function($) {
    'use strict';
    $.widget('mage.resendRecipientEmail', {
        options: {
        },

        _create: function() {
            const widget = this;
            widget._initializeButtons();
        },

        _initializeButtons: function(){
            const widget = this;
            const resendButton = document.querySelector('.js-gift-order-resend-shipment-confirmation');
            resendButton.onclick = function(){
                event.preventDefault();
                const orderId = resendButton.getAttribute('data-orderid');
                widget._resendRecipientEmail(orderId);
            }

        },
        _disableSaveButton: function(){
            const resendButton = document.querySelector('.js-gift-order-resend-shipment-confirmation');
            resendButton.classList.remove('active');
            resendButton.disabled = true;
        },

        _enableSaveButton: function(){
            const resendButton = document.querySelector('.js-gift-order-resend-shipment-confirmation');
            resendButton.classList.add('active');
            resendButton.disabled = false;
        },

        _showErrorMessage: function(message){
            alert(message);
            console.log(message);
        },

        _showSuccessMessage: function(){
            alert("Email(s) successfully sent.");
            return;
        },

        _resendRecipientEmail: function (orderId) {
            var widget = this;
            const postData = {
                order_id: orderId,
                form_key: window.FORM_KEY
            }

            const postUrl = widget.options.url;

            $.ajax( {
                showLoader: true,
                type:'POST',
                url:postUrl,
                data: postData
            })
            .success(function(data) {
                console.log(data);
                if(data.status == 'success'){
                    return widget._showSuccessMessage();
                }

                widget._showErrorMessage(data.message);
            })
            .error(function(err) {
                console.log(err);
                widget._showErrorMessage("There was a problem resending the e-mail(s), please try again");
            }).
            done(function(){
            });
        },

    });
    return $.mage.resendRecipientEmail;
});