define([
    'jquery',
    'mage/validation',
    'Magento_Ui/js/modal/confirm',
    'mage/translate'
], function($, $validation, $confirm, $t) {
    'use strict';

    $.widget('mage.deleteStripeCard', {
        options: {
        },

        _create: function() {
            var widget= this;
            widget._initializeButtons();
            //Because of difficulty getting the delete module to load through require
            widget._initializeEvents();
        },

        _initializeButtons: function(){
            var widget= this;
            $(document.body).on('click', '.js-card-delete', function(){
                var paymentCode = $(this).attr('data-paymentcode');
                widget._confirmAndDelete(paymentCode);
            });
        },

        _initializeEvents: function(){
            var widget= this;
            $(document).on('deleteStripeCard', function(e, paymentCode){
                widget._confirmAndDelete(paymentCode);
            });
        },

        _showErrorMessage: function(message){
            $confirm({
                title: "Error deleting card",
                content: message,
                actions: {
                    /** @inheritdoc */
                    confirm: function () {
                        //TODO
                    }
                }
            });
        },

        _showSuccessMessage: function(message){
            return;//Default magento message being used
            $confirm({
                title: "Success deleting card",
                content: message,
                actions: {
                    /** @inheritdoc */
                    confirm: function () {
                        //TODO
                    }
                }
            });
        },

        _showExistingSubscriptionMessage: function(message){
            $confirm({
                title: "Unable to delete",
                content: 'This card is associated with one or more auto-refill plans.  Please edit the auto-refill plan before deleting this card.',
                actions: {
                    /** @inheritdoc */
                    confirm: function () {
                        window.location.href = window.BASE_URL + 'subscription/customer/autorefill';
                    }
                },
                buttons: [{
                    text: $t('Cancel'),
                    class: 'action-secondary action-dismiss',
    
                    /**
                     * Click handler.
                     */
                    click: function (event) {
                        this.closeModal(event);
                    }
                }, {
                    text: $t('Go to auto-refills'),
                    class: 'action-primary action-accept',
    
                    /**
                     * Click handler.
                     */
                    click: function (event) {
                        this.closeModal(event, true);
                    }
                }]
            });
        },

        _confirmAndDelete: function(paymentCode){
            var widget = this;
            $confirm({
                title: "Are you sure?",
                content: 'You\'d like to delete this payment?',
                actions: {
                    /** @inheritdoc */
                    confirm: function () {
                        widget._deleteCard(paymentCode);
                    }
                },
                buttons: [{
                    text: $t('Cancel'),
                    class: 'action-secondary action-dismiss',
    
                    /**
                     * Click handler.
                     */
                    click: function (event) {
                        this.closeModal(event);
                    }
                }, {
                    text: $t('Yes'),
                    class: 'action-primary action-accept',
    
                    /**
                     * Click handler.
                     */
                    click: function (event) {
                        this.closeModal(event, true);
                    }
                }]
            });
        },

        _deleteCard: function(paymentCode){
            var widget = this;
            var formKey = document.querySelector(".js-add-card-formkey");
            var url = window.BASE_URL + "subscription/customer/deletecard";
            var request = $.ajax({
                type:'POST',
                showLoader: true,
                url:url,
                data:{form_key:formKey.value, token:paymentCode}})
                .success(function(data) {
                    if(data.status === 'error'){
                        if(data.message === 'Existing active subscription'){
                            widget._showExistingSubscriptionMessage();
                            return;
                        }
                        widget._showErrorMessage("There was an error, please try again");
                        return;
                    }
                    widget._handleDeleteCardSuccess(data);
                })
                .error(function(err) {
                    console.log(err);
                    widget._showErrorMessage("There was an error, please try again");
                });
        },

        _handleDeleteCardSuccess: function(data){
            var widget = this;
            widget._removeCardFromList(data);
            widget._showSuccessMessage("Card deleted");
        },

        _removeCardFromList: function(data){
            var cardElement = $('.js-payment-list-item[data-paymentcode="' + data.payment_code + '"]');
            cardElement.remove();
        },

    });
    return $.mage.deleteStripeCard;
});