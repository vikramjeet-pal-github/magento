<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Test\Unit\Model\Rule;

use Amasty\Shiprules\Model\Rule\AdjustmentData;
use Amasty\Shiprules\Test\Unit\Traits;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class AdjustmentDataTest
 *
 * @see AdjustmentData
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * phpcs:ignoreFile
 */
class AdjustmentDataTest extends \PHPUnit\Framework\TestCase
{
    use Traits\ObjectManagerTrait;
    use Traits\ReflectionTrait;

    /**
     * @covers AdjustmentData::setRateTotal
     * @dataProvider setRateTotalDataProvider
     */
    public function testSetRateTotal($min, $max, $result)
    {
        $model = $this->getObjectManager()->getObject(AdjustmentData::class);
        $this->setProperty($model, 'rateTotalValue', [AdjustmentData::MIN => $min, AdjustmentData::MAX => $max]);

        $model->setRateTotal(10, 20);
        $this->assertEquals($result, $this->getProperty($model, 'rateTotalValue', AdjustmentData::class));
    }

    /**
     * Data provider for setRateTotal test
     * @return array
     */
    public function setRateTotalDataProvider()
    {
        return [
            [null, null, [AdjustmentData::MIN => 10, AdjustmentData::MAX => 20]],
            [5, 6, [AdjustmentData::MIN => 5, AdjustmentData::MAX => 20]],
            [15, 30, [AdjustmentData::MIN => 10, AdjustmentData::MAX => 30]],
        ];
    }
}
