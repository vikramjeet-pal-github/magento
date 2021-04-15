<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_CommonRules
 */


namespace Amasty\CommonRules\Model\Rule\Factory;

/**
 * Class CombineHandleFactory
 */
class CombineHandleFactory extends HandlerFactoryAbstract
{
    /**
     * HandleFactory constructor.
     * @param array $handlers
     */
    public function __construct(
        array $handlers
    ) {
        $this->handlers = $handlers;
    }

    /**
     * @param string $type
     * @return array
     */
    protected function getConditionsByType($type)
    {
        $conditions = [];

        if ($condition = $this->getHandlerByType($type)) {
            $conditions[] = [
                'value' => get_class($condition),
                'label' => $condition->getConditionLabel()
            ];
        }

        return $conditions;
    }
}
