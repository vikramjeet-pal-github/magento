<?php
/*
NOTICE OF LICENSE

This source file is subject to the NekloEULA that is bundled with this package in the file ICENSE.txt.

It is also available through the world-wide-web at this URL: http://store.neklo.com/LICENSE.txt

Copyright (c)  Neklo (http://store.neklo.com/)
*/


namespace Neklo\XTableRate\Model\Carrier\Tablerate;

class DataHashGenerator
{
    /**
     * @param array $data
     *
     * @return string
     */
    public function getHash(array $data)
    {
        $countryId = $data['dest_country_id'];
        $regionId = $data['dest_region_id'];
        $zipCode = $data['dest_zip'];
        $conditionValue = $data['condition_value'];
        $shippingName = $data['shipping_name'];

        return sprintf(
            "%s-%d-%s-%s-%F",
            $countryId,
            $regionId,
            $zipCode,
            $shippingName,
            $conditionValue
        );
    }
}
