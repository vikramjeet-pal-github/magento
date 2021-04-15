/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'jquery/ui',
    'mage/translate'
], function ($, confirm, $ui, $t) {
    'use strict';

    $.widget('mage.address', {
        /**
         * Options common to all instances of this widget.
         * @type {Object}
         */
        options: {
            deleteConfirmMessage: $.mage.__('You\'d like to delete this address?'),
            deleteTitle: $.mage.__('Are you sure?')
        },

        /**
         * Bind event handlers for adding and deleting addresses.
         * @private
         */
        _create: function () {
            var options         = this.options,
                addAddress      = options.addAddress,
                deleteAddress   = options.deleteAddress;

            if (addAddress) {
                $(document).on('click', addAddress, this._addAddress.bind(this));
            }

            if (deleteAddress) {
                $(document).on('click', deleteAddress, this._deleteAddress.bind(this));
            }
        },

        _showErrorMessage: function(message){
            confirm({
                title: "Address not deleted",
                content: 'There was an error, please try again',
                actions: {
                    /** @inheritdoc */
                    confirm: function () {
                        //TODO
                    }
                }
            });
        },

        _showSuccessMessage: function(message){
            return;//Default magento message used
            confirm({
                title: "Address deleted",
                content: 'You have successfully deleted an address',
                actions: {
                    /** @inheritdoc */
                    confirm: function () {
                        //TODO
                    }
                }
            });
        },

        /**
         * Add a new address.
         * @private
         */
        _addAddress: function () {
            window.location = this.options.addAddressLocation;
        },

        /**
         * Delete the address whose id is specified in a data attribute after confirmation from the user.
         * @private
         * @param {jQuery.Event} e
         * @return {Boolean}
         */
        _deleteAddress: function (e) {
            var self = this;

            confirm({
                title: this.options.deleteTitle,
                content: this.options.deleteConfirmMessage,
                actions: {
                /** @inheritdoc */
                    confirm: function () {
                        if (typeof $(e.target).parent().data('addressid') !== 'undefined') {
                            var addressId = $(e.target).parent().data('addressid');
                            self._submitAjaxDeleteRequest(addressId);
                        } else {
                            var addressId = $(e.target).data('addressid');
                            self._submitAjaxDeleteRequest(addressId);
                        }
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

            return false;
        },

        _submitAjaxDeleteRequest: function(addressId) {
            var self = this;
            var url = self.options.baseUrl + "subscription/customer/deletecustomeraddress";
            var request = $.ajax({
                type:'POST',
                url:url,
                data:{addressId:addressId}
                })
                .success(function(data) {
                    console.log(data);
                    if(data.status === 'error'){
                        if(data.message === 'Existing subscription'){
                            self._unableToDeleteAssociated();
                            return;
                        }
                        self._showErrorMessage(data.message);
                        return;
                    }
                    self._deleteAddressFromList(data.addressId);
                    self._showSuccessMessage('Address successfully deleted');
                })
                .error(function(err) {
                    console.log(err);
                    self._showErrorMessage('There was an error, please try again later');
                });
        },

        _deleteAddressFromList: function(addressId){
            $('.subscription-address-item[data-addressid="' + addressId+ '"]').remove();
        },

        /**
         * Show unable to delete message with redirect option
         * @private
         * @param {jQuery.Event} e
         * @return {Boolean}
         */
        _unableToDeleteAssociated: function (e) {
            var self = this;

            confirm({
                title: "Unable to delete",
                content: "This address is associated with one or more auto-refill plans.  Please edit the auto-refill plan before deleting this address.",
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

            return false;
        },

        
    });

    return $.mage.address;
});
