<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Model\ResourceModel;

use Amasty\Shiprules\Api\Data\RuleInterface;

/**
 * Rule resource class.
 */
class Rule extends \Amasty\CommonRules\Model\ResourceModel\AbstractRule
{
    const TABLE_NAME = 'amasty_shiprules_rule';
    const ATTRIBUTE_TABLE_NAME = 'amasty_shiprules_attribute';

    /**
     * Model Initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, RuleInterface::RULE_ID);
    }

    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        foreach (\Amasty\Shiprules\Model\ConstantsInterface::FIELDS as $field) {
            $value = $object->getData($field);

            if (is_array($value)) {
                if ($field == 'methods') {
                    $carriers = [];

                    foreach ($value as $key => $shipMethod) {
                        if (strpos($shipMethod, '_') === false) {
                            $carriers[] = $shipMethod;

                            unset($value[$key]);
                        }
                    }
                    $object->setCarriers(implode(',', $carriers));
                }

                $object->setData($field, implode(',', $value));
            }
        }

        return parent::_beforeSave($object);
    }
}
