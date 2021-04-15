<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Model;

interface ConstantsInterface
{
    const REGISTRY_KEY = 'current_amasty_shiprules_rule';
    const SECTION_KEY = 'amshiprules';
    const DATA_PERSISTOR_FORM = 'amasty_shiprules_form_data';

    const FIELDS = [
        'stores',
        'cust_groups',
        'methods',
        'carriers',
        'days',
        'discount_id',
        'discount_id_disable'
    ];
}
