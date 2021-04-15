define([
    'jquery'
], function ($, _) {
    'use strict';
    $.widget('mage.netsuiteValidation', {

        options: {
        },

        _create: function() {
            this.element.find('input[name="firstname"]').attr('maxlength', '32');
            this.element.find('input[name="lastname"]').attr('maxlength', '32');
            this.element.find('input[name="telephone"]').removeAttr('maxlength');
        }

    });

    return $.mage.netsuiteValidation;

});