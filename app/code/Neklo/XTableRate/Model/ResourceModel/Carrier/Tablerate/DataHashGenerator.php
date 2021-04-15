<?php
/*
NOTICE OF LICENSE

This source file is subject to the NekloEULA that is bundled with this package in the file ICENSE.txt.

It is also available through the world-wide-web at this URL: http://store.neklo.com/LICENSE.txt

Copyright (c)  Neklo (http://store.neklo.com/)
*/


namespace Neklo\XTableRate\Model\ResourceModel\Carrier\Tablerate;

class DataHashGenerator extends \Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate\DataHashGenerator
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
        $conditionValue = $data['condition_value'] * 1.0;
        $conditionName = $data['condition_name'];

        $hash = sprintf(
            "%s-%d-%s-%s-%s-%F",
            $countryId,
            $regionId,
            $zipCode,
            $conditionName,
            md5($data['shipping_name']),
            $conditionValue
        );

        return $hash;
    }
}
