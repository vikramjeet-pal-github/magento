<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprestriction
 */


namespace Amasty\Shiprestriction\Controller\Adminhtml\Rule;

/**
 * Duplicate Action
 */
class Duplicate extends \Amasty\CommonRules\Controller\Adminhtml\Rule\AbstractDuplicate
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Amasty_Shiprestriction::rule';
}
