<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Setup\Operation;

/**
 * @since 2.2.3
 * Convert saved rule data to new format.
 */
class ChangeMethodsFormat
{
    /**
     * @var \Amasty\Shiprules\Model\ResourceModel\Rule\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var \Amasty\CommonRules\Model\MethodConverter
     */
    private $methods;

    public function __construct(
        \Amasty\Shiprules\Model\ResourceModel\Rule\CollectionFactory $collectionFactory,
        \Amasty\CommonRules\Model\MethodConverter $methods
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->methods = $methods;
    }

    public function execute()
    {
        $newMethods = $this->methods->getCarrierMethods();

        /** @var \Amasty\Shiprules\Model\ResourceModel\Rule\Collection $collection */
        $collection = $this->collectionFactory->create();
        $rules = $collection->loadData();

        /** @var \Amasty\Shiprules\Model\Rule $rule */
        foreach ($rules as $rule) {
            $result = [];
            $oldMethods = $rule->getMethods();

            $oldMethods = str_replace("\r\n", "\n", $oldMethods);
            $oldMethods = str_replace("\r", "\n", $oldMethods);
            $oldMethods = trim($oldMethods);

            if (empty($oldMethods)) {
                $rule->setMethods(implode(',', $result));

                continue;
            }

            $oldMethods = array_unique(explode("\n", $oldMethods));

            foreach ($oldMethods as $oldMethod) {
                $oldMethod = trim($oldMethod);

                foreach ($newMethods as $currentKey => $currentValue) {
                    if (stripos($currentValue, $oldMethod) !== false) {
                        $result[] = $currentKey;
                    }
                }
            }

            $rule->setMethods(implode(',', $result));
        }

        $collection->save();
    }
}
