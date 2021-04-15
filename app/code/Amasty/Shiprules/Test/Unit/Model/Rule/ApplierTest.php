<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Test\Unit\Model\Rule;

use Amasty\Shiprules\Model\Rule\Applier;
use Amasty\Shiprules\Test\Unit\Traits;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Class ApplierTest
 *
 * @see Applier
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * phpcs:ignoreFile
 */
class ApplierTest extends \PHPUnit\Framework\TestCase
{
    use Traits\ObjectManagerTrait;
    use Traits\ReflectionTrait;

    const RULE_ID = 1;
    const PRICE = 100;
    const WEIGHT = 10;
    const QTY = 1;
    const NOT_FREE_WEIGHT = 5;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateRequest
     */
    private $request;

    /**
     * @var \Amasty\Shiprules\Model\Rule
     */
    private $rule;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\Method
     */
    private $rate;

    /**
     * @var \Amasty\Shiprules\Model\Rule\Validator
     */
    private $validator;

    /**
     * @var Applier
     */
    private $model;

    /**
     * @var \Amasty\Shiprules\Model\Rule\Adjustment\Total
     */
    private $total;

    /**
     * @var \Magento\Quote\Model\Quote\Item
     */
    private $item1;

    /**
     * @var \Magento\Quote\Model\Quote\Item
     */
    private $item2;

    public function setUp()
    {
        /** @var Applier|MockObject model */
        $this->model = $this->getObjectManager()->getObject(Applier::class);
        $this->rate = $this->createMock(\Magento\Quote\Model\Quote\Address\RateResult\Method::class);

        $this->item1 = $this->getObjectManager()->getObject(\Magento\Quote\Model\Quote\Item::class);
        $this->item1->setData(['id' => '1']);

        $this->item2 = $this->getObjectManager()->getObject(\Magento\Quote\Model\Quote\Item::class);
        $this->item2->setData(['id' => '2']);

        $this->request = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address\RateRequest::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPackageValue'])
            ->getMock();

        $this->rule = $this->getMockBuilder(\Amasty\Shiprules\Model\Rule::class)
            ->disableOriginalConstructor()
            ->setMethods(['match'])
            ->getMock();

        $this->rule->setRuleId(self::RULE_ID);

        $this->validator = $this->getMockBuilder(\Amasty\Shiprules\Model\Rule\Validator::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValidRules', 'getAddressHash', 'collectAllItemsId'])
            ->getMock();

        $this->setProperty(
            $this->validator,
            'validItems',
            [self::RULE_ID => [$this->item1]],
            \Amasty\Shiprules\Model\Rule\Validator::class
        );
        $this->setProperty($this->model, 'validator', $this->validator, Applier::class);
        $this->setProperty($this->model, 'allItemsId', ['1', '2'], Applier::class);

        $this->total = $this->getMockBuilder(\Amasty\Shiprules\Model\Rule\Adjustment\Total::class)
            ->disableOriginalConstructor()
            ->setMethods(['calculate', 'getPrice', 'getWeight', 'getQty', 'getNotFreeWeight'])
            ->getMock();
    }

    /**
     * @covers Applier::getModifiedRequest
     *
     * @dataProvider getModifiedRequestDataProvider
     *
     * @throws \ReflectionException
     */
    public function testGetModifiedRequest($attr, $value, $match = true)
    {
        $this->request->expects($this->any())->method('getPackageValue')->willReturn(true);
        $this->rule->expects($this->any())->method('match')->willReturn($match);

        $this->total->expects($this->any())->method('calculate')->willReturn($this->total);
        $this->total->expects($this->any())->method('getPrice')->willReturn(self::PRICE);
        $this->total->expects($this->any())->method('getWeight')->willReturn(self::WEIGHT);
        $this->total->expects($this->any())->method('getQty')->willReturn(self::QTY);
        $this->total->expects($this->any())->method('getNotFreeWeight')->willReturn(self::NOT_FREE_WEIGHT);

        $this->setProperty($this->model, 'total', $this->total, Applier::class);

        $originalResult = $this->model->getModifiedRequest($this->rate, $this->request, $this->rule);
        $this->assertEquals($attr, $originalResult->getData($value));
    }

    /**
     * @covers Applier::canApplyAnyRule
     *
     * @throws \ReflectionException
     */
    public function testCanApplyAnyRule()
    {
        $this->validator->expects($this->any())->method('getValidRules')->willReturn([$this->rule]);
        $this->validator->expects($this->any())->method('getAddressHash')->willReturn(\sha1('test'));
        $this->validator->expects($this->any())->method('collectAllItemsId')->willReturn($this->request->getAllItems());

        $adjusment = $this->createMock(\Amasty\Shiprules\Model\Rule\AdjustmentData::class);
        $registry = $this->getMockBuilder(\Amasty\Shiprules\Model\Rule\Adjustment\Registry::class)
            ->disableOriginalConstructor()
            ->setMethods(['createForRate'])
            ->getMock();
        $registry->expects($this->any())->method('createForRate')->willReturn($adjusment);

        $this->setProperty($this->model, 'adjustmentRegistry', $registry, Applier::class);

        $originalResult = $this->model->canApplyAnyRule($this->request, [$this->rate]);
        $this->assertEquals(true, $originalResult);

        $adjusment->expects($this->never())->method('setRateTotal');
        $adjusment->expects($this->never())->method('setValue');
    }

    /**
     * Data provider for getModifiedRequest test
     * @return array
     */
    public function getModifiedRequestDataProvider()
    {
        return [
            [self::PRICE, 'package_value'],
            [self::QTY, 'package_qty'],
            [self::WEIGHT, 'package_weight'],
            [self::NOT_FREE_WEIGHT, 'free_method_weight']
        ];
    }
}
