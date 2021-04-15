<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Test\Unit\Model\Rule;

use Amasty\Shiprules\Model\Rule\Validator;
use Amasty\Shiprules\Test\Unit\Traits;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Class ValidatorTest
 *
 * @see Validator
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * phpcs:ignoreFile
 */
class ValidatorTest extends \PHPUnit\Framework\TestCase
{
    use Traits\ObjectManagerTrait;
    use Traits\ReflectionTrait;

    /**
     * @var Validator
     */
    private $model;

    /**
     * @var \Amasty\Shiprules\Model\Rule\Adjustment\Total
     */
    private $total;

    public function setUp()
    {
        $this->model = $this->getObjectManager()->getObject(Validator::class);
        $this->total = $this->getObjectManager()->getObject(\Amasty\Shiprules\Model\Rule\Adjustment\Total::class);
    }
    /**
     * @covers Validator::getValidRules
     *
     * @throws \ReflectionException
     */
    public function testGetValidRules()
    {
        $request = $this->getObjectManager()->getObject(\Magento\Quote\Model\Quote\Address\RateRequest::class);

        $customerSession = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getGroupId'])
            ->getMock();
        $customerSession->expects($this->any())->method('getGroupId')->willReturn(1);

        $addressModifier = $this->getMockBuilder(\Amasty\CommonRules\Model\Modifiers\Address::class)
            ->disableOriginalConstructor()
            ->setMethods(['modify'])
            ->getMock();
        $addressModifier->expects($this->any())->method('modify')->willReturn('shipAddress');

        $quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->setMethods(['getShippingAddress', 'getCustomerId', 'getCustomer'])
            ->disableOriginalConstructor()
            ->getMock();
        $quote->expects($this->any())->method('getShippingAddress')->willReturn('testAddress');
        $quote->expects($this->any())->method('getCustomerId')->willReturn('1');
        $quote->expects($this->any())->method('getCustomer')->willReturn($customerSession);

        $addressConditions = $this->getMockBuilder(\Amasty\CommonRules\Model\Rule\Condition\Address::class)
            ->disableOriginalConstructor()
            ->setMethods(['loadAttributeOptions', 'getAttributeOption'])
            ->getMock();
        $addressConditions->expects($this->any())->method('loadAttributeOptions')->willReturn($addressConditions);
        $addressConditions->expects($this->any())->method('getAttributeOption')->willReturn(['attr' => 'testAttributeOption']);

        $request->setAllItems([$request]);
        $request->setData('attr', 'test');
        $request->setQuote($quote);

        $storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore'])
            ->getMock();

        $store = $this->getObjectManager()->getObject(\Magento\Store\Model\Store::class);
        $store->setId(1);

        $storeManager->expects($this->any())->method('getStore')->willReturn($store);

        $ruleRepository = $this->getMockBuilder(\Amasty\Shiprules\Model\RuleRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRulesByParams'])
            ->getMock();
        $ruleRepository->expects($this->any())->method('getRulesByParams')->willReturn([]);

        $this->setProperty($this->model, 'storeManager', $storeManager, Validator::class);
        $this->setProperty($this->model, 'addressCondition', $addressConditions, Validator::class);
        $this->setProperty($this->model, 'ruleRepository', $ruleRepository, Validator::class);

        $originalResult = $this->model->getValidRules($request, $this->total);
        $this->assertEquals(false, $originalResult);
    }


    /**
     * @covers Validator::validateTotals
     *
     * @dataProvider validateTotalsDataProvider
     *
     * @throws \ReflectionException
     */
    public function testValidateTotals(
        $price,
        $weight,
        $qty,
        $notFreePrice,
        $notFreeQty,
        $notFreeWeight,
        $data,
        $expectedResult
    ) {
        $this->total->setPrice($price);
        $this->total->setWeight($weight);
        $this->total->setQty($qty);
        $this->total->setNotFreePrice($notFreePrice);
        $this->total->setNotFreeQty($notFreeQty);
        $this->total->setNotFreeWeight($notFreeWeight);

        $rule = $this->getMockBuilder(\Amasty\Shiprules\Model\Rule::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMock();
        $rule->expects($this->any())->method('getData')->willReturn($data);

        $originalResult = $this->invokeMethod($this->model, 'validateTotals', [$rule, $this->total]);
        $this->assertEquals($expectedResult, $originalResult);
    }

    /**
     * Data provider for validateTotals test
     * @return array
     */
    public function validateTotalsDataProvider()
    {
        return [
            [1,1,1,1,1,1,1, true],
            [10,10,10,10,10,10,10,true],
            [10,10,10,10,10,10,20,false],
            [10,20,30,40,50,60,-20,true]
        ];
    }
}
