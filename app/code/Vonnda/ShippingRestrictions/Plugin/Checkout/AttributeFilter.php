<?php
namespace Vonnda\ShippingRestrictions\Plugin\Checkout;

use Aheadworks\OneStepCheckout\Model\Layout\Processor\AddressAttributes;

class AttributeFilter
{

    /**
     * Changing where country options are pulled from in the checkoutProvider
     *
     * @param AddressAttributes $subject
     * @param array $result
     * @return array
     */
    public function afterProcess(AddressAttributes $subject, $result)
    {
        if (isset($result['components']['checkout']['children']['shippingAddress']['children']['shipping-address-fieldset']['children'])) { // ['country-region-zip-field-row']['children']['country_id']['imports']
            $countryKey = array_values(preg_grep('/country/', array_keys($result['components']['checkout']['children']['shippingAddress']['children']['shipping-address-fieldset']['children'])))[0]; // used to be 'country-region-zip-field-row', changed to 'included-country-field-row'
            if (isset($result['components']['checkout']['children']['shippingAddress']['children']['shipping-address-fieldset']['children'][$countryKey]['children']['country_id']['imports'])) {
                $result['components']['checkout']['children']['shippingAddress']['children']['shipping-address-fieldset']['children'][$countryKey]['children']['country_id']['imports']['initialOptions'] = 'index = checkoutProvider:dictionaries.shipping_country_id';
                $result['components']['checkout']['children']['shippingAddress']['children']['shipping-address-fieldset']['children'][$countryKey]['children']['country_id']['imports']['setOptions'] = 'index = checkoutProvider:dictionaries.shipping_country_id';
            }
        }
        return $result;
    }

}