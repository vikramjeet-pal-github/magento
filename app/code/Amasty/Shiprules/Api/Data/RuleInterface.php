<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Api\Data;

interface RuleInterface
{
    /**#@+
     * Constants defined for keys of data array
     */
    const RULE_ID = 'rule_id';
    const IS_ACTIVE = 'is_active';
    const CALC = 'calc';
    const DISCOUNT_ID = 'discount_id';
    const IGNORE_PROMO = 'ignore_promo';
    const POS = 'pos';
    const PRICE_FROM = 'price_from';
    const PRICE_TO = 'price_to';
    const WEIGHT_FROM = 'weight_from';
    const WEIGHT_TO = 'weight_to';
    const QTY_FROM = 'qty_from';
    const QTY_TO = 'qty_to';
    const RATE_BASE = 'rate_base';
    const RATE_FIXED = 'rate_fixed';
    const WEIGHT_FIXED = 'weight_fixed';
    const RATE_PERCENT = 'rate_percent';
    const RATE_MIN = 'rate_min';
    const RATE_MAX = 'rate_max';
    const SHIP_MIN = 'ship_min';
    const SHIP_MAX = 'ship_max';
    const HANDLING = 'handling';
    const NAME = 'name';
    const DAYS = 'days';
    const STORES = 'stores';
    const CUST_GROUPS = 'cust_groups';
    const CARRIERS = 'carriers';
    const METHODS = 'methods';
    const COUPON = 'coupon';
    const CONDITIONS_SERIALIZED = 'conditions_serialized';
    const ACTIONS_SERIALIZED = 'actions_serialized';
    const OUT_OF_STOCK = 'out_of_stock';
    const TIME_FROM = 'time_from';
    const TIME_TO = 'time_to';
    const COUPON_DISABLE = 'coupon_disable';
    const DISCOUNT_ID_DISABLE = 'discount_id_disable';
    const FOR_ADMIN = 'for_admin';
    /**#@-*/

    /**
     * @return int
     */
    public function getRuleId();

    /**
     * @param int $ruleId
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setRuleId($ruleId);

    /**
     * @return int
     */
    public function getIsActive();

    /**
     * @param int $isActive
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setIsActive($isActive);

    /**
     * @return int
     */
    public function getCalc();

    /**
     * @param int $calc
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setCalc($calc);

    /**
     * @return string
     */
    public function getDiscountId();

    /**
     * @param string $discountId
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setDiscountId($discountId);

    /**
     * @return int
     */
    public function getIgnorePromo();

    /**
     * @param int $ignorePromo
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setIgnorePromo($ignorePromo);

    /**
     * @return int
     */
    public function getPos();

    /**
     * @param int $pos
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setPos($pos);

    /**
     * @return float
     */
    public function getPriceFrom();

    /**
     * @param float $priceFrom
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setPriceFrom($priceFrom);

    /**
     * @return float
     */
    public function getPriceTo();

    /**
     * @param float $priceTo
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setPriceTo($priceTo);

    /**
     * @return float
     */
    public function getWeightFrom();

    /**
     * @param float $weightFrom
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setWeightFrom($weightFrom);

    /**
     * @return float
     */
    public function getWeightTo();

    /**
     * @param float $weightTo
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setWeightTo($weightTo);

    /**
     * @return int
     */
    public function getQtyFrom();

    /**
     * @param int $qtyFrom
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setQtyFrom($qtyFrom);

    /**
     * @return int
     */
    public function getQtyTo();

    /**
     * @param int $qtyTo
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setQtyTo($qtyTo);

    /**
     * @return float
     */
    public function getRateBase();

    /**
     * @param float $rateBase
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setRateBase($rateBase);

    /**
     * @return float
     */
    public function getRateFixed();

    /**
     * @param float $rateFixed
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setRateFixed($rateFixed);

    /**
     * @return float
     */
    public function getWeightFixed();

    /**
     * @param float $weightFixed
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setWeightFixed($weightFixed);

    /**
     * @return float
     */
    public function getRatePercent();

    /**
     * @param float $ratePercent
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setRatePercent($ratePercent);

    /**
     * @return float
     */
    public function getRateMin();

    /**
     * @param float $rateMin
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setRateMin($rateMin);

    /**
     * @return float
     */
    public function getRateMax();

    /**
     * @param float $rateMax
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setRateMax($rateMax);

    /**
     * @return float
     */
    public function getShipMin();

    /**
     * @param float $shipMin
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setShipMin($shipMin);

    /**
     * @return float
     */
    public function getShipMax();

    /**
     * @param float $shipMax
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setShipMax($shipMax);

    /**
     * @return float
     */
    public function getHandling();

    /**
     * @param float $handling
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setHandling($handling);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $name
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setName($name);

    /**
     * @return string
     */
    public function getDays();

    /**
     * @param string $days
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setDays($days);

    /**
     * @return string
     */
    public function getStores();

    /**
     * @param string $stores
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setStores($stores);

    /**
     * @return string
     */
    public function getCustGroups();

    /**
     * @param string $custGroups
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setCustGroups($custGroups);

    /**
     * @return string|null
     */
    public function getCarriers();

    /**
     * @param string|null $carriers
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setCarriers($carriers);

    /**
     * @return string|null
     */
    public function getMethods();

    /**
     * @param string|null $methods
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setMethods($methods);

    /**
     * @return string|null
     */
    public function getCoupon();

    /**
     * @param string|null $coupon
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setCoupon($coupon);

    /**
     * @return string|null
     */
    public function getConditionsSerialized();

    /**
     * @param string|null $conditionsSerialized
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setConditionsSerialized($conditionsSerialized);

    /**
     * @return string|null
     */
    public function getActionsSerialized();

    /**
     * @param string|null $actionsSerialized
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setActionsSerialized($actionsSerialized);

    /**
     * @return int
     */
    public function getOutOfStock();

    /**
     * @param int $outOfStock
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setOutOfStock($outOfStock);

    /**
     * @return int|null
     */
    public function getTimeFrom();

    /**
     * @param int|null $timeFrom
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setTimeFrom($timeFrom);

    /**
     * @return int|null
     */
    public function getTimeTo();

    /**
     * @param int|null $timeTo
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setTimeTo($timeTo);

    /**
     * @return string|null
     */
    public function getCouponDisable();

    /**
     * @param string|null $couponDisable
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setCouponDisable($couponDisable);

    /**
     * @return string|null
     */
    public function getDiscountIdDisable();

    /**
     * @param string|null $discountIdDisable
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setDiscountIdDisable($discountIdDisable);

    /**
     * @return int
     */
    public function getForAdmin();

    /**
     * @param int $forAdmin
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface
     */
    public function setForAdmin($forAdmin);
}
