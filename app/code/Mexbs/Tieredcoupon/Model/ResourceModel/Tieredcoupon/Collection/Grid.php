<?php
namespace Mexbs\Tieredcoupon\Model\ResourceModel\Tieredcoupon\Collection;

class Grid extends \Mexbs\Tieredcoupon\Model\ResourceModel\Tieredcoupon\Collection
{
    protected $_dbHelper;

    public function __construct(
        \Magento\Framework\DB\Helper $dbHelper,
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ){
        $this->_dbHelper = $dbHelper;
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource
        );
    }
    /**
     * Initialize db select
     *
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()
            ->columns([
                'main_tieredcoupon_id' => 'main_table.tieredcoupon_id',
                'name',
                'code',
                'is_active',
            ])
            ->joinLeft(
                ['tieredcoupon_coupon' => $this->getTable('mexbs_tieredcoupon_coupon')],
                "main_table.tieredcoupon_id=tieredcoupon_coupon.tieredcoupon_id")
            ->joinLeft(
                ['salesrule' => $this->getTable('salesrule_coupon')],
                "tieredcoupon_coupon.coupon_id=salesrule.coupon_id",
                []
            )->group("main_table.tieredcoupon_id");
        $this->_dbHelper->addGroupConcatColumn(
            $this->getSelect(),
            'sub_coupon_codes',
            ['salesrule.code']
        );
        return $this;
    }
}
