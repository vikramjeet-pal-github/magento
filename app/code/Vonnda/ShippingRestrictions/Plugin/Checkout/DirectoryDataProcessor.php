<?php
/**
 * DirectoryDataProcessor.php
 */

namespace Vonnda\ShippingRestrictions\Plugin\Checkout;

use Vonnda\ShippingRestrictions\Model\Shipping\Restrictions as ShippingRestrictions;

class DirectoryDataProcessor
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
    public function afterProcess($subject, $result)
    {
        if (isset($result["components"]["checkoutProvider"]["dictionaries"])) {
            $result['components']['checkoutProvider']['dictionaries']['shipping_country_id'] = $this->restrictions->getAllowedShippingCountriesOptionArray();
        }
        return $result;
    }
}
