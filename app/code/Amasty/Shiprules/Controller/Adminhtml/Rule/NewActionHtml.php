<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Controller\Adminhtml\Rule;

/**
 * Class for getting html of selected Action.
 */
class NewActionHtml extends \Amasty\CommonRules\Controller\Adminhtml\Rule\AbstractCondition
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Amasty_Shiprules::rule';

    public function execute()
    {
        $this->newConditions('actions');
    }
}
