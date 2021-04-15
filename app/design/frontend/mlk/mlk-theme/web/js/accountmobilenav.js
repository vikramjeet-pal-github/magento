define([
    'jquery'
], function ($, _) {
    'use strict';
    $.widget('mage.accountMobileNav', {
        options: {
        },
        _create: function() {
            var _self = this;
            _self.createMenu();
            _self.menuAction();
        },

        createMenu: function() {
            var menu  = $('#account-nav').html();
            if( $('#account-nav .nav.item.current').length > 0 ) {
                if( $('#account-nav .nav.item.current a').length > 0 ) {
                    var current = $('#account-nav .nav.item.current a').html();
                } else {
                    var current = $('#account-nav .nav.item.current').html();
                }
            } else {
                var current = $('h1.page-title').html();
            }


            $('<div id="mob-menu" />').insertAfter('.page-header');
            $('#mob-menu').append('<a class="current-menu-item" href="#">' + current + '</a>' + menu);

            $('.header.panel > .header.links .dropdown li').each(function(){
                $('#mob-menu .nav.items').append('<li class="nav item">' + $(this).html() + '</li>');
            });
        },

        menuAction: function(){
            $('#mob-menu > .current-menu-item').on('click', function(){
                $(this).toggleClass('active');
                $('.page-wrapper').toggleClass('menu-on');
                $('#mob-menu ul.nav.items').slideToggle();
            });
        }


    });
    return $.mage.accountMobileNav;
});