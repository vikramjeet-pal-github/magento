<?php
/*
NOTICE OF LICENSE

This source file is subject to the NekloEULA that is bundled with this package in the file ICENSE.txt.

It is also available through the world-wide-web at this URL: http://store.neklo.com/LICENSE.txt

Copyright (c)  Neklo (http://store.neklo.com/)
*/


namespace Neklo\XTableRate\Block\Adminhtml\Shipping\Carrier\Tablerate;

use Magento\OfflineShipping\Block\Adminhtml\Carrier;

class Grid extends Carrier\Tablerate\Grid
{

    protected function _prepareColumns()
    {
        $this->addColumnAfter(
            'shipping_name',
            [
                'header'  => __('Shipping Name'),
                'index'   => 'shipping_name',
                'default' => '',
            ],
            'dest_zip'
        );
        return parent::_prepareColumns();
    }
}
