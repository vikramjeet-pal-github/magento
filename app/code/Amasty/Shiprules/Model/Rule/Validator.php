<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Model\Rule;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProductType;

/**
 * Multiple rule validator.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Validator
{
    /**
     * @var array ... => \Amasty\Shiprules\Model\Rule[]
     */
    private $storage = [];

    /**
     * @var array ruleId => \Magento\Quote\Model\Quote\Item[]
     */
    private $validItems = [];

    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Amasty\CommonRules\Model\Modifiers\Address
     */
    private $addressModifier;

    /**
     * @var \Amasty\CommonRules\Model\Validator\Backorder
     */
    private $backorderValidator;

    /**
     * @var \Amasty\CommonRules\Model\Validator\SalesRule
     */
    private $salesRuleValidator;

    /**
     * @var \Amasty\CommonRules\Model\Rule\Condition\Address
     */
    private $addressCondition;

    /**
     * @var \Amasty\Shiprules\Api\RuleRepositoryInterface
     */
    private $ruleRepository;

    public function __construct(
        \Magento\Framework\App\State $appState,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Amasty\CommonRules\Model\Modifiers\Address $addressModifier,
        \Amasty\CommonRules\Model\Validator\Backorder $backorderValidator,
        \Amasty\CommonRules\Model\Validator\SalesRule $salesRuleValidator,
        \Amasty\CommonRules\Model\Rule\Condition\Address $addressCondition,
        \Amasty\Shiprules\Api\RuleRepositoryInterface $ruleRepository
    ) {
        $this->appState = $appState;
        $this->storeManager = $storeManager;
        $this->addressModifier = $addressModifier;
        $this->backorderValidator = $backorderValidator;
        $this->salesRuleValidator = $salesRuleValidator;
        $this->addressCondition = $addressCondition;
        $this->ruleRepository = $ruleRepository;
    }

    /**
     * reset storage
     */
    public function reset()
    {
        $this->storage = [];
        $this->validItems = [];
    }

    /**
     * Get valid rules for current request. Save valid rules to hash.
     *
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @param Adjustment\Total $total
     *
     * @return \Amasty\Shiprules\Model\Rule[]
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getValidRules(\Magento\Quote\Model\Quote\Address\RateRequest $request, Adjustment\Total $total)
    {
        $allItems = $request->getAllItems();
        if (empty($allItems)) {
            return [];
        }
        $modifiedAddress = $this->addressModifier->modify(
            current($allItems)->getQuote()->getShippingAddress(),
            $request
        );
        $hash = $this->getAddressHash($request);

        if (isset($this->storage[$hash])) {
            return $this->storage[$hash];
        }
        $this->storage[$hash] = false;

        /** @var $rule \Amasty\Shiprules\Model\Rule */
        foreach ($this->getAllRules($this->getCustomerGroupId($request)) as $rule) {
            $rule->afterLoad();
            /** @var \Magento\Quote\Model\Quote\Item[] $validItems */
            $validItems = $this->getValidItems($rule, $request->getAllItems());
            if (empty($validItems)) {
                continue;
            }
            $total->calculate($validItems, $hash . $rule->getRuleId(), $request->getFreeShipping());

            if ($this->salesRuleValidator->validate($rule, $allItems) // Validate rule by coupon code and conditions
                && $rule->validate($modifiedAddress, $allItems)
                && $this->validateTotals($rule, $total)
            ) {
                $this->storage[$hash][] = $rule;
            }
        }

        return $this->storage[$hash];
    }

    /**
     * @param \Amasty\Shiprules\Model\Rule $rule
     * @param \Magento\Quote\Model\Quote\Item[] $allItems
     *
     * @return \Magento\Quote\Model\Quote\Item[]
     */
    public function getValidItems(\Amasty\Shiprules\Model\Rule $rule, $allItems = [])
    {
        if (isset($this->validItems[$rule->getRuleId()]) && empty($allItems)) {
            return $this->validItems[$rule->getRuleId()];
        }

        /** @var \Amasty\CommonRules\Model\Rule\Condition\Product\Combine $actions */
        $actions = $rule->getActions();
        $this->validItems[$rule->getRuleId()] = [];
        /**
         * We need to get all items passed by action
         *
         * @var \Magento\Quote\Model\Quote\Item $item
         */
        foreach ($allItems as $item) {
            if ($item->getParentItem()) {
                continue;
            }

            if ($item->getProduct()->getTypeId() == ConfigurableProductType::TYPE_CODE) {
                foreach ($item->getChildren() as $child) {
                    if ($actions->validate($child)) {
                        $this->validItems[$rule->getRuleId()][$item->getId()] = $item;
                        break;
                    }
                }
            }

            if ($actions->validate($item)) {
                $this->validItems[$rule->getRuleId()][$item->getItemId()] = $item;
            }
        }

        return $this->validItems[$rule->getRuleId()];
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item[] $allItem
     *
     * @return array
     */
    public function collectAllItemsId($allItem)
    {
        $items = [];

        foreach ($allItem as $item) {
            if ($item->getParentItem()) {
                continue;
            }

            $items[] = $item->getId();
        }

        return $items;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     *
     * @return string
     */
    public function getAddressHash(\Magento\Quote\Model\Quote\Address\RateRequest $request)
    {
        $addressAttributes = $this->addressCondition->loadAttributeOptions()->getAttributeOption();
        $addressAttributes += [ //Multishipping attr
            'dest_country_id' => 'dest_country_id',
            'dest_region_id' => 'dest_region_id',
            'dest_city' => 'dest_city',
            'dest_postcode' => 'dest_postcode',
        ];
        $hash = '';

        foreach ($addressAttributes as $code => $label) {
            $hash .= $request->getData($code) . $label;
        }

        return \hash('md5', $hash);
    }

    /**
     * @param \Amasty\Shiprules\Model\Rule $rule
     * @param Adjustment\Total $total
     *
     * @return bool
     */
    private function validateTotals(\Amasty\Shiprules\Model\Rule $rule, Adjustment\Total $total)
    {
        if ($rule->getIgnorePromo()) {
            $totalData = [
                'price' => $total->getPrice(),
                'qty' => $total->getQty(),
                'weight' => $total->getWeight()
            ];
        } else {
            $totalData = [
                'price' => $total->getNotFreePrice(),
                'qty' => $total->getNotFreeQty(),
                'weight' => $total->getNotFreeWeight(),
            ];
        }

        foreach ($totalData as $key => $value) {
            if ($rule->getData($key . '_from') > 0
                && $value < $rule->getData($key . '_from')
            ) {
                return false;
            }

            if ($rule->getData($key . '_to') > 0
                && $value > $rule->getData($key . '_to')
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param int $customerId
     *
     * @return \Amasty\Shiprules\Api\Data\RuleInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getAllRules($customerId)
    {
        return $this->ruleRepository->getRulesByParams(
            $this->storeManager->getStore()->getId(),
            $customerId,
            $this->appState->getAreaCode() == \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE
        );
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     *
     * @return int
     */
    private function getCustomerGroupId(\Magento\Quote\Model\Quote\Address\RateRequest $request)
    {
        $groupId = 0;
        $firstItem = current($request->getAllItems());

        if ($firstItem->getQuote()->getCustomerId()) {
            $groupId = $firstItem->getQuote()->getCustomer()->getGroupId();
        }

        return $groupId;
    }
}
