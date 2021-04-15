<?php
/**
 * Copyright © 2020 Grazitti . All rights reserved.
 */

namespace Grazitti\Maginate\Controller\Adminhtml\Logs;

class NewAction extends \Grazitti\Maginate\Controller\Adminhtml\Logs
{

    public function execute()
    {
        $this->_forward('edit');
    }
}
