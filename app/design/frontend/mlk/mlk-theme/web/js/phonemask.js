define([
    'jquery', 'mask'
], function ($, mask) {
    'use strict';
    $.widget('mage.PhoneMask', {
        options: {
        },
        _create: function() {
            $('input[name="telephone"], input[name="billing-telephone"], input[name="flow-billing-telephone"], input[name="flow-telephone"]').mask('(000) 000-000000000');
        }

    });
    return $.mage.PhoneMask;
});