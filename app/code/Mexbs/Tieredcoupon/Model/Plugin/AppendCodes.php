<?php
namespace Mexbs\Tieredcoupon\Model\Plugin;

use Magento\Framework\DB\Select;

class AppendCodes
{
    protected $tcHelper;
    protected $productMetaData;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_date;

    /**
     * @param \Mexbs\Tieredcoupon\Model\Tieredcoupon $ruleResource
     */
    public function __construct(
        \Mexbs\Tieredcoupon\Helper\Data $tcHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $date,
        \Magento\Framework\App\ProductMetadataInterface $productMetaData
    )
    {
        $this->tcHelper = $tcHelper;
        $this->productMetaData = $productMetaData;
        $this->_date = $date;
    }

    protected function getMagentoVersion(){
        return $this->productMetaData->getVersion();
    }

    /**
     * if the current coupon code is a tier, replace the where with its sub coupons
     */
    public function aroundSetValidationFilter(
        \Magento\SalesRule\Model\ResourceModel\Rule\Collection $subject,
        \Closure $proceed,
        $websiteId,
        $customerGroupId,
        $couponCode = '',
        $now = null,
        \Magento\Quote\Model\Quote\Address $address = null
        )
    {
        $tieredcoupon = $this->tcHelper->getTieredCouponByCouponCode($couponCode);


        $result = $proceed(
            $websiteId,
            $customerGroupId,
            $couponCode,
            $now,
            $address
        );

        if($tieredcoupon && $tieredcoupon->getId() && $tieredcoupon->getIsActive()){
            $subCouponCodes = $tieredcoupon->getSubCouponCodes();
            $magentoVersion = $this->getMagentoVersion();
            if(version_compare($magentoVersion, "2.3.0", "=")
                || version_compare($magentoVersion, "2.2.8", "<")
            ){
                $select = $subject->getSelect();
                $select->reset('where');

                if ($now === null) {
                    $now = $this->_date->date()->format('Y-m-d');
                }

                $select->where(
                    'from_date is null or from_date <= ?',
                    $now
                )->where(
                        'to_date is null or to_date >= ?',
                        $now
                    );

                $subject->addFieldToFilter('is_active', 1);

                $connection = $subject->getConnection();
                $noCouponWhereCondition = $connection->quoteInto(
                    'main_table.coupon_type = ? ',
                    \Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON
                );

                $orWhereConditions = [
                    $connection->quoteInto(
                        '(main_table.coupon_type = ? AND rule_coupons.type = 0)',
                        \Magento\SalesRule\Model\Rule::COUPON_TYPE_AUTO
                    ),
                    $connection->quoteInto(
                        '(main_table.coupon_type = ? AND main_table.use_auto_generation = 1 AND rule_coupons.type = 1)',
                        \Magento\SalesRule\Model\Rule::COUPON_TYPE_SPECIFIC
                    ),
                    $connection->quoteInto(
                        '(main_table.coupon_type = ? AND main_table.use_auto_generation = 0 AND rule_coupons.type = 0)',
                        \Magento\SalesRule\Model\Rule::COUPON_TYPE_SPECIFIC
                    ),
                ];

                $andWhereConditions = [
                    $connection->quoteInto(
                        'rule_coupons.code in (?)',
                        array_values($subCouponCodes)
                    ),
                    $connection->quoteInto(
                        '(rule_coupons.expiration_date IS NULL OR rule_coupons.expiration_date >= ?)',
                        $this->_date->date()->format('Y-m-d')
                    ),
                ];

                $orWhereCondition = implode(' OR ', $orWhereConditions);
                $andWhereCondition = implode(' AND ', $andWhereConditions);

                $select->where(
                    $noCouponWhereCondition . ' OR ((' . $orWhereCondition . ') AND ' . $andWhereCondition . ')'
                );
            }elseif(version_compare($magentoVersion, "2.3.1", "=")
                || version_compare($magentoVersion, "2.2.8", "=")
            ){
                $select = $subject->getSelect();
                $select->reset('where');

                if ($now === null) {
                    $now = $this->_date->date()->format('Y-m-d');
                }

                $select->where(
                    'from_date is null or from_date <= ?',
                    $now
                )->where(
                        'to_date is null or to_date >= ?',
                        $now
                    );

                $subject->addFieldToFilter('is_active', 1);

                $connection = $subject->getConnection();
                $relatedRulesIds = $this->getCouponRelatedRuleIds($subCouponCodes, $subject);

                $noCouponWhereCondition = $connection->quoteInto(
                    'main_table.coupon_type = ?',
                    \Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON
                );

                $select->where(
                    $noCouponWhereCondition . ' OR main_table.rule_id IN (?)',
                    $relatedRulesIds,
                    Select::TYPE_CONDITION
                );
            }elseif(version_compare($magentoVersion, "2.3.1", ">")
                ||
                (
                    version_compare($magentoVersion, "2.2.8", ">")
                    && version_compare($magentoVersion, "2.3.0", "<")
                )
            ){
                $select = $subject->getSelect();

                $from = $select->getPart('from');
                if($from && isset($from['t']['tableName'])){
                    $tablesSelect = $from['t']['tableName'];
                    if($tablesSelect && ($tablesSelect instanceof \Magento\Framework\DB\Select)){
                        $union = $tablesSelect->getPart('union');
                        if($union && isset($union[1][0])){
                            $secondUnionPartSelect = $union[1][0];
                            if($secondUnionPartSelect && ($secondUnionPartSelect instanceof \Magento\Framework\DB\Select)){
                                $secondUnionPartFrom = $secondUnionPartSelect->getPart('from');
                                if($secondUnionPartFrom && isset($secondUnionPartFrom['rule_coupons']['joinCondition'])){
                                    $joinCondition = $secondUnionPartFrom['rule_coupons']['joinCondition'];

                                    $couponCodePattern = '/rule_coupons\\.code = \'.+\'/';
                                    $couponCodeReplacement = $subject->getConnection()->quoteInto("rule_coupons.code IN (?)", $subCouponCodes);

                                    $subCouponsJoinCondition = preg_replace($couponCodePattern, $couponCodeReplacement, $joinCondition, -1);
                                    $secondUnionPartFrom['rule_coupons']['joinCondition'] = $subCouponsJoinCondition;

                                    $secondUnionPartSelect->setPart('from', $secondUnionPartFrom);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    private function getCouponRelatedRuleIds($subCouponCodes, $collection)
    {
        $connection = $collection->getConnection();
        $select = $connection->select()->from(
            ['main_table' => $collection->getTable('salesrule')],
            'rule_id'
        );
        $select->joinLeft(
            ['rule_coupons' => $collection->getTable('salesrule_coupon')],
            $connection->quoteInto(
                'main_table.rule_id = rule_coupons.rule_id AND main_table.coupon_type != ?',
                \Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON,
                null
            )
        );

        $autoGeneratedCouponCondition = [
            $connection->quoteInto(
                "main_table.coupon_type = ?",
                \Magento\SalesRule\Model\Rule::COUPON_TYPE_AUTO
            ),
            $connection->quoteInto(
                "rule_coupons.type = ?",
                \Magento\SalesRule\Api\Data\CouponInterface::TYPE_GENERATED
            ),
        ];

        $orWhereConditions = [
            "(" . implode($autoGeneratedCouponCondition, " AND ") . ")",
            $connection->quoteInto(
                '(main_table.coupon_type = ? AND main_table.use_auto_generation = 1 AND rule_coupons.type = 1)',
                \Magento\SalesRule\Model\Rule::COUPON_TYPE_SPECIFIC
            ),
            $connection->quoteInto(
                '(main_table.coupon_type = ? AND main_table.use_auto_generation = 0 AND rule_coupons.type = 0)',
                \Magento\SalesRule\Model\Rule::COUPON_TYPE_SPECIFIC
            ),
        ];

        $andWhereConditions = [
            $connection->quoteInto(
                'rule_coupons.code in (?)',
                array_values($subCouponCodes)
            ),
            $connection->quoteInto(
                '(rule_coupons.expiration_date IS NULL OR rule_coupons.expiration_date >= ?)',
                $this->_date->date()->format('Y-m-d')
            ),
        ];

        $orWhereCondition = implode(' OR ', $orWhereConditions);
        $andWhereCondition = implode(' AND ', $andWhereConditions);

        $select->where(
            '(' . $orWhereCondition . ') AND ' . $andWhereCondition,
            null,
            Select::TYPE_CONDITION
        );
        $select->group('main_table.rule_id');

        return $connection->fetchCol($select);
    }
}
