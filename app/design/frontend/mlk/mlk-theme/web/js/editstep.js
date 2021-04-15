define([
    'jquery'
], function($, _) {
    'use strict';
    $.widget('mage.editStep', {
        options: {},

        _create: function() {
            this._editStep();
        },

        _editStep: function() {
            var _self = this;
            var btn = _self.element;
            if (btn.hasClass('checkout-edit-step')) {
                btn.on('click', function() {
                    var parentBlock = _self.element.closest('.aw-onestep-groups_item');
                    $('.aw-onestep-groups_item.active').removeClass('active');
                    parentBlock.find('.group-preview').hide();
                    parentBlock.removeClass('done').addClass('active');
                    parentBlock.find('.checkout-edit-step-cancel').css('display','inline-block');
                });
            } else {
                btn.on('click', function() {
                    var parentBlock = _self.element.closest('.aw-onestep-groups_item');
                    parentBlock.find('.checkout-edit-step-cancel').hide();
                    parentBlock.removeClass('active').addClass('done');
                    parentBlock.find('.group-preview').show();
                    $('.aw-onestep-groups_item').not('.done').first().addClass('active');
                });
            }
        }

    });
    return $.mage.editStep;
});