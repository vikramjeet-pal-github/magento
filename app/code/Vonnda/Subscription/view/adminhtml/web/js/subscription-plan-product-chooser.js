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

    $.widget('mage.subscriptionPlanProduct', {
        /**
         * Create widget
         * @private
         */
        _create: function () {
            this.initialProductList = [...window.subscriptionPlanProducts];
            this.subscriptionPlanProducts = window.subscriptionPlanProducts;
            this.dialogSubscriptionPlanProducts = [];
            this._disableQtyFilter();
            this._disablePriceOverrideFilter();
            this._clearRows();
            this._setSyncShowGridToState(this.initialProductList);
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
                gridPopup;

            popup.modal({
                type: 'slide',
                innerScroll: true,
                title: $.mage.__('Configure Tier Products'),
                modalClass: 'grouped',

                /** @inheritdoc */
                open: function () {
                    $(this).addClass('admin__scope-old'); // ToDo UI: remove with old styles removal
                },
                buttons: [{
                    id: 'grouped-product-dialog-apply-button',
                    text: $.mage.__('Accept Product Configuration'),
                    'class': 'action-primary action-add',

                    /** @inheritdoc */
                    click: function () {
                        widget._updateTemplateAndProducts(widget.dialogSubscriptionPlanProducts);
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
                    const qtyField = row[0].querySelector('input[type="text"]');
                    const product = widget._getProductInfoByRow(row[0]);
                    if (element.is(':checked')) {
                        if(!qtyField.value){
                            qtyField.value = 1;
                        }
                        widget._addProduct(product);
                    } else {
                        qtyField.value = '';
                        widget._deleteProduct(product);
                    }
                }, this)
            );

            popup.on(
                'blur',
                '[data-role=row] [data-column=qty] input',
                $.proxy(function (event) {
                    const element = $(event.target);
                    const row = element.closest('[data-role=row]');
                    const product = widget._getProductInfoByRow(row[0]);
                    const qty = element[0].value;
                    if(qty != element[0].defaultValue){
                        const checkbox = element[0].parentNode.parentNode.querySelector('input[type="checkbox"]');
                        if(checkbox.checked == true){
                            widget._updateProduct(product);
                        }                            
                    }
                }, this)
            );

            popup.on(
                'blur',
                '[data-role=row] [data-column=price_override] input',
                $.proxy(function (event) {
                    const element = $(event.target);
                    const row = element.closest('[data-role=row]');
                    const product = widget._getProductInfoByRow(row[0]);
                    const checkbox = element[0].parentNode.parentNode.querySelector('input[type="checkbox"]');
                    const overrideNum = parseFloat(product.price_override);
                    if(isNaN(overrideNum)){
                        product.price_override = '';
                    }
                    if(checkbox.checked == true){
                        widget._updateProduct(product);
                    }                            
                }, this)
            );

            gridPopup = $(this.options.gridPopup).data('gridObject');

            $('[data-role=add-product]').on('click', function (event) {
                event.preventDefault();
                popup.modal('openModal');
                widget.dialogSubscriptionPlanProducts = [...widget.subscriptionPlanProducts];
                widget._setCurrentGridState(widget.dialogSubscriptionPlanProducts);
            });

            $('#' + gridPopup.containerId).on('gridajaxsettings', function (event, ajaxSettings) {
                
            }).on('gridajax', function (event, ajaxRequest) {
                ajaxRequest.done(function () {
                    widget._disableQtyFilter();
                    widget._disablePriceOverrideFilter();
                    widget._setCurrentGridState(widget.dialogSubscriptionPlanProducts);
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
        _getProductInfoByRow: function (row) {
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
            rowFields['id'] = rowFields.entity_id;
            if(!rowFields.qty){
                rowFields.qty = 1;
            }
            if(!rowFields.name || rowFields.name == " "){
                rowFields.name = "No product name";
            }
            return rowFields;
        },

        /**
         * Get all row info in object form
         * @private
         */
        _getCurrentGridState: function () {
            const grid = document.querySelector(".admin__data-grid-wrap tbody");
            const rows = grid.querySelectorAll("tr");
            const gridState = [];
            rows.forEach(row => {
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
                })
                gridState.push(rowFields);
            })
            return gridState;
        },

        /**
         * Set all row info (checked and quantity)
         * @private
         */
        _setCurrentGridState: function (productList) {
            const rows = [...document.querySelectorAll("tbody tr")];
            productList.forEach( product => {
                let targetRow = rows.filter( row => {
                    const cell = row.querySelector('td[data-column="entity_id"]');
                    if(cell){
                        return cell.innerText == product.id;
                    }
                });
                if(targetRow){
                    this._setRowValue(targetRow, true, product.qty, product.price_override);
                }
            });
            this._clearInputsForUncheckedGridRows(rows);
        },

        /**
         * Set all row info (checked and quantity)
         * @private
         */
        _setRowValue: function (row, checkedValue, qty, priceOverride) {
            if(row.length == 0){
                return;
            }
            const targetRow = row[0];
            const checkbox = targetRow.querySelector('td input[type="checkbox"]');
            const qtyCell = targetRow.querySelector('td input[name="qty"]');
            const priceOverrideCell = targetRow.querySelector('td input[name="price_override"]');
            
            checkbox.checked = checkedValue;
            qtyCell.value = qty;
            if(priceOverride){
                priceOverrideCell.value = parseFloat(priceOverride).toFixed(2);
            }
        },

        /**
         * Set input value
         * @private
         */
        _setInputValue: function (selectedProductList) {
            const filteredList = selectedProductList.map( element => {
                return {id:element.id, qty:element.qty, price_override:element.price_override}
                });
            $('input[name="subscriptionPlan[subscription_products]"]').val(JSON.stringify(filteredList)).change();
        },

        /**
         * Set all row info (checked and quantity)
         * @private
         */
        _clearRows:function () {
            let currentRows = container.querySelectorAll('.product-chooser__item');
            currentRows.forEach( row => row.parentNode.removeChild(row));
            let noProductMessage = document.querySelector('.js-no-product-message');
            if(noProductMessage){
                noProductMessage.parentNode.removeChild(noProductMessage);
            }
        },

        /**
         * Set all row info (checked and quantity)
         * @private
         */
        _generateRow: function (cells) {
            const newRow = document.createElement("tr");
            newRow.classList.add('product-chooser__item');
            newRow.classList.add('data-row');
            cells.forEach( cell => {
                const element = document.createElement("td");
                if(cell.type === "currency"){
                    element.innerHTML = this._preparePriceOverrideFieldForShowGrid(cell.value, "N/A");
                } else {
                    element.innerHTML = cell.value;
                }
                newRow.appendChild(element);
            });

            return newRow;
        },

        /**
         * Set all row info (checked and quantity)
         * @private
         */
        _setSyncShowGridToState: function(productsState) {
            const productChooserContainer = document.querySelector('.product-chooser__container .data-grid');
            if(productsState.length === 0){
                const message = document.createElement("h4");
                message.classList.add('js-no-product-message');
                message.innerText = "No products configured for tier";
                productChooserContainer.appendChild(message);
                return;
            }
            productsState.forEach( item => {
                const newRow = this._generateRow(
                    [{value:item.name},
                     {value:item.sku}, 
                     {value:item.qty},
                     {value:item.price_override, type:"currency"}]);
                productChooserContainer.appendChild(newRow);
            });
        },

        /**
         * Set all row info (checked and quantity)
         * @private
         */
        _addProduct: function(product) {
            this.dialogSubscriptionPlanProducts.push(product);
        },

        /**
         * Set all row info (checked and quantity)
         * @private
         */
        _deleteProduct: function(product) {
            this.dialogSubscriptionPlanProducts = this.dialogSubscriptionPlanProducts .filter( currentProduct => {
                return currentProduct.id != product.id
            });
        },

        /**
         * Set all row info (checked and quantity)
         * @private
         */
        _updateProduct: function(product) {
            this.dialogSubscriptionPlanProducts = this.dialogSubscriptionPlanProducts .filter( currentProduct => {
                return currentProduct.id != product.id
            });
            this.dialogSubscriptionPlanProducts.push(product);
        },

        /**
         * 
         * @private
         */
        _disableQtyFilter: function() {
            const qtyInput = document.querySelector('.data-grid-filters input[name="qty"]');
            qtyInput.disabled = true;
        },

        /**
         * 
         * @private
         */
        _disablePriceOverrideFilter: function() {
            const priceOverrideInput = document.querySelector('.data-grid-filters input[name="price_override"]');
            priceOverrideInput.disabled = true;
        },

        /**
         * This is where we apply are changes
         * @private
         */
        _updateTemplateAndProducts: function(subscriptionPlanProducts) {
            this.subscriptionPlanProducts = [...subscriptionPlanProducts];
            this._clearRows();
            this._setSyncShowGridToState(this.subscriptionPlanProducts);
            this._setInputValue(this.subscriptionPlanProducts);
        },

        /**
         * 
         * @private
         */
        _clearInputsForUncheckedGridRows: function(rows) {
            rows.forEach( row => {
                const checkbox = row.querySelector('td input[type="checkbox"]');
                if(checkbox && !checkbox.checked){
                    const qtyCell = row.querySelector('td input[name="qty"]');
                    qtyCell.value = '';
                    const priceOverrideCell = row.querySelector('td input[name="price_override"]');
                    priceOverrideCell.value = '';
                }
            });
        },

        /**
         * Prepare price for grid field
         * @private
         */
        _preparePriceOverrideFieldForShowGrid: function(value, defaultVal) {
            const isNull = value === null;
            const isEmptyString = value === '';
            if(isNull || isEmptyString){
                return defaultVal;
            } else {
                const numValue = parseFloat(value);
                return '$' + numValue.toFixed(2);
            }
        },
    });

    return $.mage.subscriptionPlanProduct;
});