/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'jquery',
    'mage/template',
    'jquery/ui',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'mage/adminhtml/grid'
], function ($, mageTemplate) {
    'use strict';

    $.widget('mage.subscriptionCustomerDevice', {
        /**
         * Create widget
         * @private
         */
        _create: function () {
            this._initialSubscriptionDevice = {...window.subscriptionDevice};
            this._subscriptionDevice = window.subscriptionDevice;
            this._dialogSubscriptionDevice = {...window.subscriptionDevice};
            this._setDeviceInfo(this._initialSubscriptionDevice);
            this._bindDialog();
            this._updateGridVisibility();
        },

        /**
         * Create modal for show product
         *
         * @private
         */
        _bindDialog: function () {
            var widget = this,
                popup = $('[data-role=add-product-dialog]'),
                devicePopup;

            popup.modal({
                type: 'slide',
                innerScroll: true,
                title: $.mage.__('Choose Device'),
                modalClass: 'grouped',

                /** @inheritdoc */
                open: function () {
                    $(this).addClass('admin__scope-old'); // ToDo UI: remove with old styles removal
                },
                buttons: [{
                    id: 'grouped-product-dialog-apply-button',
                    text: $.mage.__('Accept Device'),
                    'class': 'action-primary action-add',

                    /** @inheritdoc */
                    click: function () {
                        widget._updateTemplateAndDevice(widget._dialogSubscriptionDevice);
                        widget._updateGridVisibility();
                        popup.modal('closeModal');
                    }
                }]
            });

            popup.on('click', '[data-role=row]', function (event) {
                var target = $(event.target);
                if (!target.is('input')) {
                    target.closest('[data-role=row]')
                        .find('[data-column=entity_ids] input')
                        .prop('checked', function (element, value) {
                            return !value;
                        })
                        .trigger('change');
                }
            });

            popup.on(
                'change',
                '[data-role=row] [data-column=entity_ids] input',
                $.proxy(function (event) {
                    const element = $(event.target);
                    const row = element.closest('[data-role=row]');
                    const device = widget._getDeviceInfoByRow(row[0]);
                    if (element.is(':checked')) {
                        widget._addDevice(device);
                    } else {
                        widget._deleteDevice(device);
                    }
                }, this)
            );

            devicePopup = $(this.options.devicePopup).data('gridObject');

            $('[data-role=configure-device]').on('click', function (event) {
                event.preventDefault();
                popup.modal('openModal');
                widget._dialogSubscriptionDevice = {...widget._subscriptionDevice};
                widget._setCurrentGridState(widget._dialogSubscriptionDevice);
            });

            $('#' + devicePopup.containerId).on('gridajaxsettings', function (event, ajaxSettings) {
                console.log(event);
                console.log(ajaxSettings);
                
            }).on('gridajax', function (event, ajaxRequest) {
                console.log(ajaxRequest);
                ajaxRequest.done(function () {
                    widget._setCurrentGridState(widget._dialogSubscriptionDevice);
                });
            });
        },

        /**
         * Show or hide message
         * @private
         */
        _updateGridVisibility: function () {
            var showGrid = this.element.find('[data-role=id]').length > 0;
            this.element.find('.grid-container').toggle(showGrid);
            this.element.find('.no-products-message').toggle(!showGrid);
        },

        //get row info
        /**
         * Get correspnding row info for product id
         * @private
         */
        _getDeviceInfoByRow: function (row) {
            const rowFields = {};
            const cells = row.querySelectorAll("td");
            cells.forEach( cell => {
                const fieldName = cell.getAttribute("data-column");
                const input = cell.querySelector('input');
                if(input && input.type == 'checkbox'){
                    rowFields['checked'] = input.checked;
                } else if(input) {
                    rowFields[fieldName] = input.value;
                } else {
                    rowFields[fieldName] = cell.innerText;
                }
            });
            return rowFields;
        },

        /**
         * Set check applicable box
         * @private
         */
        _setCurrentGridState: function (device) {
            const rows = [...document.querySelectorAll("tbody tr")];
            let targetRow = rows.filter( row => {
                const cell = row.querySelector('td[data-column="entity_id"]');
                if(cell){
                    return cell.innerText == device.entity_id;
                }
            });
            if(targetRow){
                this._setRowValue(targetRow, true);
            }
        },

        /**
         * Set all row info (checked and quantity)
         * @private
         */
        _setRowValue: function (row, checkedValue) {
            if(row.length == 0){
                return;
            }
            const targetRow = row[0];
            const checkbox = targetRow.querySelector('td input[type="checkbox"]');
            checkbox.checked = checkedValue;
        },

        /**
         * Set input value
         * @private
         */
        _setInputValue: function (device) {
            $('input[name="subscriptionCustomer[device_id]"]').val(device.entity_id).change();
        },

        /**
         * Set all row info (checked and quantity)
         * @private
         */
        _clearDeviceInfo:function () {
            const deviceInfoContainer = document.querySelector('.js-device-chooser__info-box');
            deviceInfoContainer.innerHTML = '';
        },

        /**
         * Set all row info (checked and quantity)
         * @private
         */
        _setDeviceInfo: function (device) {
            const deviceInfoContainer = document.querySelector('.js-device-chooser__info-box');
            let deviceInfo = "No device currently associated to subscription.";
            if(device.entity_id.trim().length > 0) {
                deviceInfo = `Device Id: ${device.entity_id}`;

                if (device.serial_number.trim().length > 0) {
                    deviceInfo += `, Device Serial Number: ${device.serial_number}`;
                }

                if (device.sku.trim().length > 0) {
                    deviceInfo += `, Device Sku: ${device.sku}`;
                }
            }
            deviceInfoContainer.innerHTML = deviceInfo;
        },

        /**
         * Set all row info (checked and quantity)
         * @private
         */
        _addDevice: function(device) {
            this._clearOtherCheckboxes(device);
            this._dialogSubscriptionDevice = {...device};
        },

        /**
         * Set all row info (checked and quantity)
         * @private
         */
        _deleteDevice: function(device) {
            this._dialogSubscriptionDevice = {};
        },

        /**
         * This is where we apply are changes
         * @private
         */
        _updateTemplateAndDevice: function(device) {
            this._subscriptionDevice = {...device};
            this._clearDeviceInfo();
            this._setDeviceInfo(this._subscriptionDevice);
            this._setInputValue(this._subscriptionDevice);
        },

        /**
         * This is where we apply are changes
         * @private
         */
        _clearOtherCheckboxes: function(device) {
            const checkboxes = [...document.querySelectorAll('#device_grid_popup_table input[type="checkbox"]')];
            checkboxes.forEach( checkbox => {
                if(checkbox.value != device.entity_id){
                    checkbox.checked = false;
                }
            })
        },

    });

    return $.mage.subscriptionCustomerDevice;
});