<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Test\Unit\Model\Rule\Adjustment;

use Amasty\Shiprules\Model\Rule;
use Amasty\Shiprules\Model\Rule\Adjustment\Calculator;
use Amasty\Shiprules\Model\Rule\Adjustment\Total;
use Amasty\Shiprules\Test\Unit\Traits;
use Magento\Quote\Model\Quote\Address\RateResult\Method as Rate;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class CalculatorTest
 *
 * @see Calculator
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * phpcs:ignoreFile
 */
class CalculatorTest extends \PHPUnit\Framework\TestCase
{
    use Traits\ObjectManagerTrait;
    use Traits\ReflectionTrait;

    /**
     * @covers Calculator::calculateByRule
     */
    public function testCalculateByRule()
    {
        $total = $this->getObjectManager()->getObject(Total::class);
        $model = $this->getObjectManager()->getObject(
            Calculator::class,
            [
                'total' => $total
            ]
        );
        $rule = $this->createPartialMock(Rule::class, []);
        $priceCurrency = $this->createMock(\Magento\Framework\Pricing\PriceCurrencyInterface::class);
        $rate = $this->getObjectManager()->getObject(Rate::class, ['priceCurrency' => $priceCurrency]);

        $priceCurrency->expects($this->once())->method('round')->willReturnArgument(0);

        $this->setProperty($total, 'hash', 'test1', Total::class);
        $total->setNotFreePrice(200);
        $total->setNotFreeQty(10);
        $total->setNotFreeWeight(20);
        $rule->setIgnorePromo(false);
        $rule->setRateFixed(5);
        $rule->setRatePercent(4);
        $rule->setWeightFixed(3);
        $rule->setCalc(0);
        $rate->setPrice(30);
        $rate->setHandling(15);

        $this->assertEquals(0, $model->calculateByRule($rule, $rate, 'test', true));
        $this->assertEquals(88, $model->calculateByRule($rule, $rate, 'test1', false));

        $this->setProperty($total, 'hash', 'test2', Total::class);
        $total->setPrice(10);
        $total->setQty(0);
        $total->getWeight(30);
        $rule->setIgnorePromo(true);
        $rule->setCalc(2);
        $this->assertEquals(-0.4, $model->calculateByRule($rule, $rate, 'test2', false));
    }

    /**
     * @covers Calculator::checkChangeBoundary
     */
    public function testCheckChangeBoundary()
    {
        $model = $this->createPartialMock(Calculator::class, []);
        $rule = $this->createPartialMock(Rule::class, []);

        $rule->setRateMax(5.5);
        $this->assertEquals(5.5, $this->invokeMethod($model, 'checkChangeBoundary', [-10, $rule]));

        $rule->setRateMin(15);
        $this->assertEquals(15, $this->invokeMethod($model, 'checkChangeBoundary', [10, $rule]));
    }
}
