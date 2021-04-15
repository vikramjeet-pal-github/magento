define([
    'jquery',
    'domReady!'
], function ($) {
    'use strict';

    $.widget('molekule.successPage', {
        options: {
            affirmStepID: "#affirm-save-cart-step",
            finalSuccessID: "#final-success-page",
            affirmFooter: ".affirm-flow-footer"
        },

        /**
         * @private
         */
        _create: function () {
            this.fullScreenLoader();
            this._toggleSteps();
            this._hideInitLoader();
        },

        _hideInitLoader: function () {
          $('.initial-loader').remove();
        },

        /**
         * Toggle content on page depands of payment gateway
         * @private
         */
        _toggleSteps: function () {
            let currentPaymentMethod = this.options.currentPaymentMethod;
            if (currentPaymentMethod === 'affirm_gateway') {
                $(this.options.affirmStepID).show();
                $(this.options.affirmFooter).show();
                $('body').addClass('affirm-save-card-step');
                this._utagData();
            } else {
                $(this.options.finalSuccessID).show();
                $('body').addClass('final-success');
            }
            this.fullScreenLoader('stop');
        },

        /**
         * Adding Tealium utagData
         * @private
         */
        _utagData: function () {
            setTimeout(function () {
                var tealiumTag = window.utag;
                var oldUDO = window.utag_data;
                if(typeof window.utag !== "undefined"){
                    oldUDO["event_platform"] = "Affirm page";
                    oldUDO["page_type"] = "affirm_page";
                    tealiumTag.view(oldUDO);
                }
            }, 5000);

        },

        /**
         * Full page loader actions
         * @param state
         * @param containerId
         */
        fullScreenLoader: function (state, containerId = 'body') {
            (state === 'stop') ? $(containerId).trigger('processStop') : $(containerId).trigger('processStart');
        }
    });

    return $.molekule.successPage;
});
