define([
    'jquery'
], function ($, _) {
    'use strict';
    $.widget('mage.cartQty', {
        options: {
        },
        _create: function() {
            this._addSelect();
            this._openList();
            this._addDefaultSelected();
            this._selectValue();
        },

        _addSelect: function () {
            var _self = this;
            var qtyInput = _self.element;
            var qtyParent = qtyInput.closest('.mlk-qty');
            var listHtml = '<div class="mlk-qty__listwrap"><ul class="mlk-qty__list">\n';
            var j = 100;
            if (qtyInput.data('maxqty') != undefined) {
                j = qtyInput.data('maxqty')+1;
            }
            for (var i = 1; i < j; i++){
                listHtml = listHtml + '<li data-value="'+i+'">'+i+'</li>\n';
            }
            listHtml = listHtml + '</ul></div>';

            qtyParent.append(listHtml);
        },

        _addDefaultSelected: function(){
            var _self = this;
            var qtyInput = _self.element;
            var qtyList = qtyInput.next('.mlk-qty__listwrap');
            var defaultVal = qtyInput.val();
            qtyList.find('li[data-value="'+ defaultVal +'"]').addClass('selected');
        },

        _openList: function() {
            var _self = this;
            var qtyInput = _self.element;
            var qtyParent = qtyInput.closest('.mlk-qty');
            var qtyList = qtyParent.find('.mlk-qty__listwrap');
            qtyInput.on('click', function(){
                qtyList.fadeToggle(100);
            });

            $(document).on('mouseup', function(e)
            {
                // if the target of the click isn't the container nor a descendant of the container
                if (!qtyParent.is(e.target) && qtyParent.has(e.target).length === 0)
                {
                    qtyList.fadeOut(100);
                }
            });
        },

        _selectValue: function(){
            var _self = this;
            var qtyInput = _self.element;
            var qtyList = qtyInput.next('.mlk-qty__listwrap');
            var qtyListItem = qtyList.find('li');
            qtyListItem.on('click', function(){
                if(!$(this).hasClass('selected')){
                    qtyList.find('li.selected').removeClass('selected');
                    $(this).addClass('selected');
                    var selectedValue = parseFloat($(this).html());
                    qtyInput.val(selectedValue);
                    qtyInput.trigger('change');
                }
                qtyList.fadeOut(100);
            });
        }

    });
    return $.mage.cartQty;
});
