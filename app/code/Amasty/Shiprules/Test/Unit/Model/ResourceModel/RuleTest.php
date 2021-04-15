<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Test\Unit\Model\ResourceModel;

use Amasty\Shiprules\Model\ResourceModel\Rule;
use Amasty\Shiprules\Test\Unit\Traits;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class RuleTest
 *
 * @see Rule
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * phpcs:ignoreFile
 */
class RuleTest extends \PHPUnit\Framework\TestCase
{
    use Traits\ObjectManagerTrait;
    use Traits\ReflectionTrait;

    /**
     * @covers Rule::_beforeSave
     */
    public function testBeforeSave()
    {
        $model = $this->getObjectManager()->getObject(Rule::class);
        $object = $this->createPartialMock(\Magento\Framework\Model\AbstractModel::class, []);
        $object->setData('stores', [12]);
        $object->setData('methods', ['test']);

        $this->invokeMethod($model, '_beforeSave', [$object]);
        $this->assertEquals('test', $object->getCarriers());
    }
}
