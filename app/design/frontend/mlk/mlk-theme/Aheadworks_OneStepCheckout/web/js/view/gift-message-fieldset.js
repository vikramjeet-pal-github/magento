define([
    'jquery',
    'underscore',
    'ko',
    'uiComponent',
    'uiRegistry',
    'uiLayout',
    'mageUtils'
], function (
    $,
    _,
    ko,
    Component,
    registry,
    layout,
    utils
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Aheadworks_OneStepCheckout/gift-message-fieldset'
        },
        cachedMessage: {},

        /** {@inheritdoc} */
        initialize: function() {
            this._super().initGiftMessageRenderer();
            return this;
        },

        /** {@inheritdoc} */
        initObservable: function() {
            this._super().observe({
                isActive: true
            });
            return this;
        },

        /**
         * Check if module is active
         * @returns {Boolean}
         */
        isActiveModule: function() {
            return this.isActive();
        },

        /**
         * @returns {Component}
         */
        initGiftMessageRenderer: function() {
            var rendererTemplate,
                templateData,
                renderer;
            rendererTemplate = {
                parent: '${ $.$data.parentName }',
                name: '${ $.$data.name }'
            };
            templateData = {
                parentName: this.name,
                name: 'gift-message'
            };
            renderer = utils.template(rendererTemplate, templateData);
            layout([renderer]);
            return this;
        }
        
    });
});
