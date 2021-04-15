<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Plugin\SalesRule\Setup;

/**
 * Converting serialized rule data to JSON.
 */
class UpgradeData
{
    /**
     * @var \Amasty\Base\Setup\SerializedFieldDataConverter
     */
    private $fieldDataConverter;

    public function __construct(\Amasty\Base\Setup\SerializedFieldDataConverter $fieldDataConverter)
    {
        $this->fieldDataConverter = $fieldDataConverter;
    }

    /**
     * @param \Magento\SalesRule\Setup\UpgradeData $subject
     * @param $result
     * @return mixed
     */
    public function afterConvertSerializedDataToJson(\Magento\SalesRule\Setup\UpgradeData $subject, $result)
    {
        $fields = ['conditions_serialized', 'actions_serialized'];
        $this->fieldDataConverter->convertSerializedDataToJson('amasty_shiprules_rule', 'rule_id', $fields);

        return $result;
    }
}
