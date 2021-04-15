<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Test\Unit\Model\Rule\Adjustment;

use Amasty\Shiprules\Model\Rule\Adjustment\Total;
use Amasty\Shiprules\Test\Unit\Traits;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class TotalTest
 *
 * @see Total
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * phpcs:ignoreFile
 */
class TotalTest extends \PHPUnit\Framework\TestCase
{
    use Traits\ObjectManagerTrait;
    use Traits\ReflectionTrait;

    /**
     * @covers Total::calculate
     */
    public function testCalculate()
    {
        $model = $this->createPartialMock(Total::class, ['calculateByBundle', 'calculateByItem']);
        $item1 = $this->createPartialMock(\Magento\Quote\Model\Quote\Item::class,
            ['getHasChildren', 'getProduct']);
        $item2 = $this->createPartialMock(\Magento\Quote\Model\Quote\Item::class,
            ['getHasChildren', 'getProduct', 'isShipSeparately']);
        $product = $this->createPartialMock(\Magento\Catalog\Model\Product::class, ['isVirtual']);

        $item1->expects($this->any())->method('getProduct')->willReturn($product);
        $item2->expects($this->any())->method('getProduct')->willReturn($product);
        $item2->expects($this->any())->method('getHasChildren')->willReturn(true);
        $item2->expects($this->any())->method('isShipSeparately')->willReturn(true);
        $product->expects($this->any())->method('isVirtual')->willReturn(false);
        $model->expects($this->exactly(2))->method('calculateByBundle');
        $model->expects($this->exactly(2))->method('calculateByItem');

        $this->setProperty($model, 'hash', 'test', Total::class);
        $model->setWeight(10);
        $model->setNotFreeWeight(10);

        $model->calculate([$item1, $item2], 'test', false);
        $this->assertEquals(10, $model->getWeight());
        $this->assertEquals(10, $model->getNotFreeWeight());

        $model->calculate([$item1, $item2], 'test', true);
        $this->assertEquals(0, $model->getNotFreePrice());
        $this->assertEquals(0, $model->getNotFreeWeight());
        $this->assertEquals(0, $model->getNotFreeQty());
    }

    /**
     * @covers Total::calculateByBundle
     */
    public function testCalculateByBundle()
    {
        $model = $this->createPartialMock(Total::class, ['calculateByItem']);
        $item = $this->createPartialMock(
            \Magento\Quote\Model\Quote\Item::class,
            ['getQty', 'getChildren', 'getWeight', 'getProduct']
        );
        $child1 = $this->createPartialMock(\Magento\Quote\Model\Quote\Item::class, ['getProduct', 'getQty']);
        $child2 = $this->createPartialMock(\Magento\Quote\Model\Quote\Item::class, ['getProduct', 'getQty']);
        $product1 = $this->createPartialMock(\Magento\Catalog\Model\Product::class, ['isVirtual', 'getWeightType']);
        $product2 = $this->createPartialMock(\Magento\Catalog\Model\Product::class, ['isVirtual', 'getWeightType']);

        $product1->expects($this->any())->method('isVirtual')->willReturn(false);
        $product2->expects($this->any())->method('isVirtual')->willReturn(true);
        $product1->expects($this->any())->method('getWeightType')->willReturn(5);
        $product2->expects($this->any())->method('getWeightType')->willReturn(5);
        $child1->expects($this->any())->method('getProduct')->willReturn($product1);
        $child2->expects($this->any())->method('getProduct')->willReturn($product2);
        $child1->expects($this->any())->method('getQty')->willReturn(10);
        $child2->expects($this->any())->method('getQty')->willReturn(20);
        $item->expects($this->any())->method('getChildren')->willReturn([$child1, $child2]);
        $item->expects($this->any())->method('getQty')->willReturn(10);
        $item->expects($this->any())->method('getWeight')->willReturn(20);
        $item->expects($this->any())->method('getProduct')->willReturn($product1);

        $this->invokeMethod($model, 'calculateByBundle', [$item]);

        $this->assertEquals(200, $model->getWeight());
        $this->assertEquals(200, $model->getNotFreeWeight());
    }

    /**
     * @covers Total::calculateByItem
     */
    public function testCalculateByItem()
    {
        $model = $this->getObjectManager()->getObject(Total::class);
        $item = $this->createPartialMock(\Magento\Quote\Model\Quote\Item::class, ['getQty', 'getBasePrice']);

        $item->expects($this->any())->method('getQty')->willReturn(10);
        $item->expects($this->any())->method('getBasePrice')->willReturn(20);

        $this->invokeMethod($model, 'calculateByItem', [$item, 5]);

        $this->assertEquals(1000, $model->getPrice());
        $this->assertEquals(1000, $model->getNotFreePrice());
    }

    /**
     * @covers Total::getFreeQty
     */
    public function testGetFreeQty()
    {
        $model = $this->getObjectManager()->getObject(Total::class);
        $item = $this->createPartialMock(\Magento\Quote\Model\Quote\Item::class, ['getQty', 'getFreeShipping']);

        $item->expects($this->any())->method('getQty')->willReturn(10);
        $item->expects($this->any())->method('getFreeShipping')->willReturnOnConsecutiveCalls(0, 20, 'test');

        $this->assertEquals(0, $this->invokeMethod($model, 'getFreeQty', [$item]));
        $this->assertEquals(20, $this->invokeMethod($model, 'getFreeQty', [$item]));
        $this->assertEquals(10, $this->invokeMethod($model, 'getFreeQty', [$item]));
    }
}
