<?php
/**
 * AddressFilter.php
 */

namespace Vonnda\ShippingRestrictions\Plugin\Checkout;

use Vonnda\ShippingRestrictions\Model\Shipping\Restrictions as ShippingRestrictions;

class AddressFilter
{
    /** @property ShippingRestrictions $restrictions */
    protected $restrictions;

    /**
     * @param ShippingRestrictions $restrictions
     */
    public function __construct(
        ShippingRestrictions $restrictions
    ) {
        $this->restrictions = $restrictions;
    }

    /**
     * @param  $subject
     * @param array $result
     */
    public function afterGetCheckoutConfig($subject, $result)
    {
        // Remove address with restricted shipping country from customer data
        if(isset($result["customerData"]["addresses"]) && is_array($result["customerData"]["addresses"])){
            $customerAddresses = [];
            $allowedCountries = $this->restrictions->getAllowedShippingCountries();
            foreach($result["customerData"]["addresses"] as $_address){
                if(in_array($_address['country_id'], $allowedCountries)){
                    $customerAddresses[] = $_address;
                }
            }
            $result["customerData"]["addresses"] = $customerAddresses;
        }
        return $result;
    }
}
