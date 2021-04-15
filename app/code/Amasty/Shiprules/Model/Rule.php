<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Model;

use Amasty\Shiprules\Api\Data\RuleInterface;

/**
 * Class Rule
 */
class Rule extends \Amasty\CommonRules\Model\Rule implements RuleInterface
{
    /**
     * _construct
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Rule::class);
        parent::_construct();

        $this->subtotalModifier->setSectionConfig(ConstantsInterface::SECTION_KEY);
    }

    /**
     * Prepare model for edit in form.
     * Restore array values, if it was saved.
     * Merge Carriers and Methods columns to edit in Shipping Carriers and Methods field.
     *
     * @return $this
     */
    public function prepareForEdit()
    {
        foreach (ConstantsInterface::FIELDS as $field) {
            $val = $this->getData($field);

            if (!is_array($val)) {
                $this->setData($field, explode(',', $val));
            }
        }

        $value = $this->getCarriers();

        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (is_array($value)) {
            $this->setMethods(array_merge($value, $this->getMethods()));
        }

        return $this;
    }

    /**
     * @param \Magento\Framework\DataObject $object
     * @param array|null $items
     *
     * @return bool
     */
    public function validate(\Magento\Framework\DataObject $object, $items = null)
    {
        if ($items && !$this->backorderValidator->validate($this, $items)) {
            return false;
        }

        if ($object instanceof \Magento\Quote\Model\Quote\Address) {
            $object = $this->subtotalModifier->modify($object);
        }

        return $this->getConditions()->validateNotModel($object);
    }

    /**
     * Initialize rule model data from array
     *
     * @param array $data
     *
     * @return Rule
     */
    public function loadPost(array $data)
    {
        $arr = $this->_convertFlatToRecursive($data);

        if (isset($arr['conditions'])) {
            $this->getConditions()->setConditions([])->loadArray(
                $arr['conditions'][1]
            );
        }

        if (isset($arr['actions'])) {
            $this->getActions()->setActions([])->loadArray(
                $arr['actions'][1],
                'actions'
            );
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getStores()
    {
        $stores = $this->_getData(RuleInterface::STORES);

        if (is_string($stores)) {
            $stores = explode(',', $stores);
            $this->setStores($stores);
        }

        return $this->_getData(RuleInterface::STORES);
    }

    /**
     * @inheritdoc
     */
    public function getRuleId()
    {
        return $this->_getData(RuleInterface::RULE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setRuleId($ruleId)
    {
        $this->setData(RuleInterface::RULE_ID, $ruleId);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getIsActive()
    {
        return $this->_getData(RuleInterface::IS_ACTIVE);
    }

    /**
     * @inheritdoc
     */
    public function setIsActive($isActive)
    {
        $this->setData(RuleInterface::IS_ACTIVE, $isActive);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCalc()
    {
        return $this->_getData(RuleInterface::CALC);
    }

    /**
     * @inheritdoc
     */
    public function setCalc($calc)
    {
        $this->setData(RuleInterface::CALC, $calc);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDiscountId()
    {
        return $this->_getData(RuleInterface::DISCOUNT_ID);
    }

    /**
     * @inheritdoc
     */
    public function setDiscountId($discountId)
    {
        $this->setData(RuleInterface::DISCOUNT_ID, $discountId);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getIgnorePromo()
    {
        return $this->_getData(RuleInterface::IGNORE_PROMO);
    }

    /**
     * @inheritdoc
     */
    public function setIgnorePromo($ignorePromo)
    {
        $this->setData(RuleInterface::IGNORE_PROMO, $ignorePromo);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getPos()
    {
        return $this->_getData(RuleInterface::POS);
    }

    /**
     * @inheritdoc
     */
    public function setPos($pos)
    {
        $this->setData(RuleInterface::POS, $pos);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getPriceFrom()
    {
        return $this->_getData(RuleInterface::PRICE_FROM);
    }

    /**
     * @inheritdoc
     */
    public function setPriceFrom($priceFrom)
    {
        $this->setData(RuleInterface::PRICE_FROM, $priceFrom);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getPriceTo()
    {
        return $this->_getData(RuleInterface::PRICE_TO);
    }

    /**
     * @inheritdoc
     */
    public function setPriceTo($priceTo)
    {
        $this->setData(RuleInterface::PRICE_TO, $priceTo);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getWeightFrom()
    {
        return $this->_getData(RuleInterface::WEIGHT_FROM);
    }

    /**
     * @inheritdoc
     */
    public function setWeightFrom($weightFrom)
    {
        $this->setData(RuleInterface::WEIGHT_FROM, $weightFrom);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getWeightTo()
    {
        return $this->_getData(RuleInterface::WEIGHT_TO);
    }

    /**
     * @inheritdoc
     */
    public function setWeightTo($weightTo)
    {
        $this->setData(RuleInterface::WEIGHT_TO, $weightTo);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getQtyFrom()
    {
        return $this->_getData(RuleInterface::QTY_FROM);
    }

    /**
     * @inheritdoc
     */
    public function setQtyFrom($qtyFrom)
    {
        $this->setData(RuleInterface::QTY_FROM, $qtyFrom);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getQtyTo()
    {
        return $this->_getData(RuleInterface::QTY_TO);
    }

    /**
     * @inheritdoc
     */
    public function setQtyTo($qtyTo)
    {
        $this->setData(RuleInterface::QTY_TO, $qtyTo);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRateBase()
    {
        return $this->_getData(RuleInterface::RATE_BASE);
    }

    /**
     * @inheritdoc
     */
    public function setRateBase($rateBase)
    {
        $this->setData(RuleInterface::RATE_BASE, $rateBase);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRateFixed()
    {
        return $this->_getData(RuleInterface::RATE_FIXED);
    }

    /**
     * @inheritdoc
     */
    public function setRateFixed($rateFixed)
    {
        $this->setData(RuleInterface::RATE_FIXED, $rateFixed);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getWeightFixed()
    {
        return $this->_getData(RuleInterface::WEIGHT_FIXED);
    }

    /**
     * @inheritdoc
     */
    public function setWeightFixed($weightFixed)
    {
        $this->setData(RuleInterface::WEIGHT_FIXED, $weightFixed);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRatePercent()
    {
        return $this->_getData(RuleInterface::RATE_PERCENT);
    }

    /**
     * @inheritdoc
     */
    public function setRatePercent($ratePercent)
    {
        $this->setData(RuleInterface::RATE_PERCENT, $ratePercent);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRateMin()
    {
        return $this->_getData(RuleInterface::RATE_MIN);
    }

    /**
     * @inheritdoc
     */
    public function setRateMin($rateMin)
    {
        $this->setData(RuleInterface::RATE_MIN, $rateMin);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRateMax()
    {
        return $this->_getData(RuleInterface::RATE_MAX);
    }

    /**
     * @inheritdoc
     */
    public function setRateMax($rateMax)
    {
        $this->setData(RuleInterface::RATE_MAX, $rateMax);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getShipMin()
    {
        return $this->_getData(RuleInterface::SHIP_MIN);
    }

    /**
     * @inheritdoc
     */
    public function setShipMin($shipMin)
    {
        $this->setData(RuleInterface::SHIP_MIN, $shipMin);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getShipMax()
    {
        return $this->_getData(RuleInterface::SHIP_MAX);
    }

    /**
     * @inheritdoc
     */
    public function setShipMax($shipMax)
    {
        $this->setData(RuleInterface::SHIP_MAX, $shipMax);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getHandling()
    {
        return $this->_getData(RuleInterface::HANDLING);
    }

    /**
     * @inheritdoc
     */
    public function setHandling($handling)
    {
        $this->setData(RuleInterface::HANDLING, $handling);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->_getData(RuleInterface::NAME);
    }

    /**
     * @inheritdoc
     */
    public function setName($name)
    {
        $this->setData(RuleInterface::NAME, $name);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDays()
    {
        return $this->_getData(RuleInterface::DAYS);
    }

    /**
     * @inheritdoc
     */
    public function setDays($days)
    {
        $this->setData(RuleInterface::DAYS, $days);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setStores($stores)
    {
        $this->setData(RuleInterface::STORES, $stores);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCustGroups()
    {
        return $this->_getData(RuleInterface::CUST_GROUPS);
    }

    /**
     * @inheritdoc
     */
    public function setCustGroups($custGroups)
    {
        $this->setData(RuleInterface::CUST_GROUPS, $custGroups);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCarriers()
    {
        return $this->_getData(RuleInterface::CARRIERS);
    }

    /**
     * @inheritdoc
     */
    public function setCarriers($carriers)
    {
        $this->setData(RuleInterface::CARRIERS, $carriers);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getMethods()
    {
        return $this->_getData(RuleInterface::METHODS);
    }

    /**
     * @inheritdoc
     */
    public function setMethods($methods)
    {
        $this->setData(RuleInterface::METHODS, $methods);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCoupon()
    {
        return $this->_getData(RuleInterface::COUPON);
    }

    /**
     * @inheritdoc
     */
    public function setCoupon($coupon)
    {
        $this->setData(RuleInterface::COUPON, $coupon);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getConditionsSerialized()
    {
        return $this->_getData(RuleInterface::CONDITIONS_SERIALIZED);
    }

    /**
     * @inheritdoc
     */
    public function setConditionsSerialized($conditionsSerialized)
    {
        $this->setData(RuleInterface::CONDITIONS_SERIALIZED, $conditionsSerialized);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getActionsSerialized()
    {
        return $this->_getData(RuleInterface::ACTIONS_SERIALIZED);
    }

    /**
     * @inheritdoc
     */
    public function setActionsSerialized($actionsSerialized)
    {
        $this->setData(RuleInterface::ACTIONS_SERIALIZED, $actionsSerialized);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getOutOfStock()
    {
        return $this->_getData(RuleInterface::OUT_OF_STOCK);
    }

    /**
     * @inheritdoc
     */
    public function setOutOfStock($outOfStock)
    {
        $this->setData(RuleInterface::OUT_OF_STOCK, $outOfStock);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTimeFrom()
    {
        return $this->_getData(RuleInterface::TIME_FROM);
    }

    /**
     * @inheritdoc
     */
    public function setTimeFrom($timeFrom)
    {
        $this->setData(RuleInterface::TIME_FROM, $timeFrom);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTimeTo()
    {
        return $this->_getData(RuleInterface::TIME_TO);
    }

    /**
     * @inheritdoc
     */
    public function setTimeTo($timeTo)
    {
        $this->setData(RuleInterface::TIME_TO, $timeTo);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCouponDisable()
    {
        return $this->_getData(RuleInterface::COUPON_DISABLE);
    }

    /**
     * @inheritdoc
     */
    public function setCouponDisable($couponDisable)
    {
        $this->setData(RuleInterface::COUPON_DISABLE, $couponDisable);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDiscountIdDisable()
    {
        return $this->_getData(RuleInterface::DISCOUNT_ID_DISABLE);
    }

    /**
     * @inheritdoc
     */
    public function setDiscountIdDisable($discountIdDisable)
    {
        $this->setData(RuleInterface::DISCOUNT_ID_DISABLE, $discountIdDisable);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getForAdmin()
    {
        return $this->_getData(RuleInterface::FOR_ADMIN);
    }

    /**
     * @inheritdoc
     */
    public function setForAdmin($forAdmin)
    {
        $this->setData(RuleInterface::FOR_ADMIN, $forAdmin);

        return $this;
    }
}
