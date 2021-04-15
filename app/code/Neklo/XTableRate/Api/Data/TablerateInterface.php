<?php
/*
NOTICE OF LICENSE

This source file is subject to the NekloEULA that is bundled with this package in the file ICENSE.txt.

It is also available through the world-wide-web at this URL: http://store.neklo.com/LICENSE.txt

Copyright (c)  Neklo (http://store.neklo.com/)
*/


namespace Neklo\XTableRate\Api\Data;

interface TablerateInterface
{
    const ENTITY_ID = 'pk';
    const WEBSITE_ID = 'website_id';
    const DEST_COUNTRY_ID = 'dest_country_id';
    const DEST_REGION_ID = 'dest_region_id';
    const DEST_ZIP = 'dest_zip';
    const SHIPPING_NAME = 'shipping_name';
    const CONDITION_NAME = 'condition_name';
    const CONDITION_VALUE = 'condition_value';
    const PRICE = 'price';
    const COST = 'cost';
}
