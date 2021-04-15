<?php
/**
 * Copyright © 2020 Grazitti . All rights reserved.
 */
namespace Grazitti\Maginate\Block\Adminhtml;

class Logs extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'logs';
        $this->_blockGroup = 'Grazitti_Maginate';
        $this->_headerText = __('Marketo Logs');
        parent::_construct();
    }
}
