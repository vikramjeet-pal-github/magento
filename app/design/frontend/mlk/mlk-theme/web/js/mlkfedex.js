define([
    'jquery'
], function(
    $
) {
    'use strict';
    $.widget('mage.mlkFedEX', {
        options: {},
        _create: function() {
           this._changeFedexValues();
           this.inputOnChange();
        },

        _changeFedexValues: function(){
            var _self = this;
            var shippingTitleParent = _self.element;
            var shippingLabel = shippingTitleParent.find('.label');
            var shippingLabelHtml = shippingLabel.html();
            var shippingSubtitle = shippingTitleParent.find('.shipping-method-subtitle');

            if (( shippingSubtitle.html() == 'Home Delivery' ) ||  ( shippingSubtitle.html() == 'Ground' )) {

                shippingLabel.html(shippingLabelHtml + ' Ground');
                shippingSubtitle.html('3-7 business days');
                shippingTitleParent.addClass('fedex-shipping');

            } else if (  shippingSubtitle.html() == '2 Day' ) {

                shippingLabel.html(shippingLabelHtml + ' 2-Day');
                shippingSubtitle.html('2 business days');
                shippingTitleParent.addClass('fedex-shipping');

            } else if (  shippingSubtitle.html() == 'Priority Overnight' ) {

                shippingLabel.html(shippingLabelHtml + ' Overnight');
                shippingSubtitle.html('1 business day');
                shippingTitleParent.addClass('fedex-shipping');
            } else if ( shippingLabel.html() == 'Canada Post' ) {
                shippingLabel.html('Ground Shipping');
            }

        },

        inputOnChange: function() {
            $('.aw-onestep-groups_item.shipping-method input.radio').on('change', function(){
                if($(this).is(':checked')){
                    $('.aw-onestep-groups_item.shipping-method .checkout-next-step').removeClass('inactive');
                }
            });
        }


    });
    return $.mage.mlkFedEX;
});