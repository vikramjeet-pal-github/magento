<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Test\Unit\Model;

use Amasty\Shiprules\Model\Rule;
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
     * @var Rule
     */
    private $model;

    protected function setUp()
    {
        $this->model = $this->createPartialMock(
            Rule::class,
            []
        );
    }

    /**
     * @covers Rule::prepareForEdit
     */
    public function testPrepareForEdit()
    {
        $this->model->setData('stores', 12);
        $this->model->setData('cust_groups', [15]);
        $this->model->setCarriers('test');
        $this->model->prepareForEdit();
        $this->assertEquals(['test', ''], $this->model->getMethods());
    }

    /**
     * @covers Rule::validate
     */
    public function testValidate()
    {
        $backorderValidator = $this->createMock(\Amasty\CommonRules\Model\Validator\Backorder::class);
        $subtotalModifier = $this->createMock(\Amasty\CommonRules\Model\Modifiers\Subtotal::class);
        $conditions = $this->createMock(\Magento\Rule\Model\Condition\Combine::class);
        $object = $this->createMock(\Magento\Framework\DataObject::class);

        $backorderValidator->expects($this->any())->method('validate')->willReturnOnConsecutiveCalls(false, true, true);
        $subtotalModifier->expects($this->once())->method('modify')->willReturn(true);

        $this->setProperty($this->model, 'backorderValidator' , $backorderValidator);
        $this->setProperty($this->model, 'subtotalModifier' , $subtotalModifier);
        $this->setProperty($this->model, '_conditions' , $conditions);

        $this->assertFalse($this->model->validate($object, true));
        $this->model->validate($object, true);
        $object = $this->createMock(\Magento\Quote\Model\Quote\Address::class);
        $this->model->validate($object, true);
    }
}