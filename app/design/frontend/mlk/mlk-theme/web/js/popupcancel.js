define([
    'jquery',
    'Magento_Ui/js/modal/modal'
], function(
    $,
    modal
) {
    'use strict';
    $.widget('mage.popupCancel', {
        options: {},
        _create: function() {
            var elem = this.element.find('.cancel-anytime-link');
            var target = $('#cancel-popup-content');
            var modalOptions = {
                'type': 'popup',
                'modalClass': 'agreements-modal',
                title: ' ',
                'responsive': true,
                'innerScroll': true,
                'buttons': []
            };

            var popup = modal(modalOptions, target);

            elem.on('click', function(e){
                e.preventDefault();
                target.modal("openModal");
            });
        }



    });
    return $.mage.popupCancel;
});