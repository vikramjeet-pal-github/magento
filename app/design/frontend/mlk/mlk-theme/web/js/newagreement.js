define([
    'jquery',
    'Magento_Ui/js/modal/modal'
], function(
    $,
    modal
) {
    'use strict';
    $.widget('mage.newAgreement', {
        options: {},
        _create: function() {
            var elem = this.element;
            var target = elem.attr('data-target');
            var title = elem.attr('data-title');
            var modalOptions = {
                'type': 'popup',
                'modalClass': 'agreements-modal',
                'title': title,
                'responsive': true,
                'innerScroll': true,
                'buttons': []
            };

            var popup = modal(modalOptions, $('#'+ target));

            elem.on('click', function(e){
                e.preventDefault();
                $('#'+ target).modal("openModal");
            });
        }



    });
    return $.mage.newAgreement;
});