<?php

namespace Vonnda\Residential\Plugin;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;

class AddCompanyField
{
    public const ATTRIBUTE = 'company';

    public function afterProcess(
        LayoutProcessorInterface $subject,
        array $jsLayout
    ) {
        $shippingConfiguration = &$jsLayout['components']['checkout']['children']['shippingAddress']
            ['children']['shipping-address-fieldset']['children']['field-row-1']['children'];

        if (isset($shippingConfiguration)) {
            $field = [
                'component' => 'Magento_Ui/js/form/element/abstract',
                'config' => [
                    'customScope' => 'shippingAddress',
                    'template' => 'Vonnda_Residential/form/field',
                ],
                'dataScope' => 'shippingAddress.' . static::ATTRIBUTE,
                'description' => __('Company'),
                'formElement' => 'input',
                'label' => __('Business name (optional)'),
                'provider' => 'checkoutProvider',
                'sortOrder' => 0,
                'visible' => false
            ];

            $shippingConfiguration[static::ATTRIBUTE] = $field;
        }

        return $jsLayout;
    }
}
