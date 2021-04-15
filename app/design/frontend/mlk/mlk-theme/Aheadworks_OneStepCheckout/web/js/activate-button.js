requirejs([
    'jquery'
], function ($, _) {
    'use strict';
    $(document).ready(function () {
        $(document.body).on('keyup input', '.aw-onestep-groups_item input:visible', function() {
            if ($(this).attr('aria-required') == 'true') {
                var isValidForm = true;
                $.each($(this).parents('.aw-onestep-groups_item').find('input'), function () {
                    if (!$(this).val().trim() && $(this).attr('aria-required') == 'true') {
                        isValidForm = false;
                    }
                });
                if (isValidForm) {
                    $(this).parents('.aw-onestep-groups_item').find('.checkout-next-step').addClass('active').removeClass('inactive');
                } else {
                    $(this).parents('.aw-onestep-groups_item').find('.checkout-next-step').removeClass('active').addClass('inactive');
                }
            }
        });
    });
});