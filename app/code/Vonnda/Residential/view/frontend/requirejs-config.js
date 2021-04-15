var config = {
    config: {
        mixins: {
            'Aheadworks_OneStepCheckout/js/action/get-sections-details': {
                'Vonnda_Residential/js/action/get-sections-details-mixin': true
            }
        },
        map: {
            '*': {
                residentialCheckbox: 'Vonnda_Residential/js/form/residential-checkbox'
            }
        }
    }
};