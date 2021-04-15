<?php

namespace Vonnda\Residential\Plugin;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;

class AddResidential
{
    /** @constant string ATTRIBUTE */
    public const ATTRIBUTE = 'is_residential';

    /** @constant string AREA */
    public const AREA = 'shipping-address-fieldset';

    /** @constant string FIELD */
    public const FIELD = 'field-row-1';
    
    public function afterProcess(
        LayoutProcessorInterface $subject,
        array $jsLayout
    ) {

        $layout = &$jsLayout['components']['checkout']['children']['shippingAddress']
            ['children'][static::AREA]['children'][static::FIELD]['children'];

        if (isset($layout)) {
            $layout[static::ATTRIBUTE] = [
                'component' => 'Vonnda_Residential/js/form/residential-checkbox',
                'config' => [
                    'customScope' => 'shippingAddress.custom_attributes',
                    'template' => 'Vonnda_Residential/form/checkbox',
                    'prefer' => 'checkbox',
                ],
                'dataScope' => 'shippingAddress.custom_attributes.' . static::ATTRIBUTE,
                'description' => __('This is a non-medical purchase going to a business.'),
                'provider' => 'checkoutProvider',
                'sortOrder' => 0,
                'validation' => [
                    'required-entry' => true,
                ],
                'visible' => true,
                'additionalClasses' => 'residential-checkbox',
                'valueMap' => [
                    'false' => true,
                    'true' => false,
                ],
            ];
        }

        return $jsLayout;
    }
}
