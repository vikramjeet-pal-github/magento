<?php
namespace Mexbs\Tieredcoupon\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Tieredcoupon extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('mexbs_tieredcoupon', 'tieredcoupon_id');
    }

    /**
     * Get sub coupon ids
     *
     * @param \Mexbs\Tieredcoupon\Model\Tieredcoupon $tieredcoupon
     * @return array
     */
    public function getSubCouponIds($tieredcoupon)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable('mexbs_tieredcoupon_coupon'),
            ['coupon_id']
        )->where(
                'tieredcoupon_id = :tieredcoupon_id'
            );
        $bind = ['tieredcoupon_id' => (int)$tieredcoupon->getId()];

        return $this->getConnection()->fetchCol($select, $bind);
    }

    /**
     * Get sub coupon codes
     *
     * @param \Mexbs\Tieredcoupon\Model\Tieredcoupon $tieredcoupon
     * @return array
     */
    public function getSubCouponCodes($tieredcoupon)
    {
        $select = $this->getConnection()->select()
        ->from(
            ['tieredcoupon_coupon' => $this->getTable('mexbs_tieredcoupon_coupon')],
            [])
        ->joinInner(
                ['salesrule' => $this->getTable('salesrule_coupon')],
                "tieredcoupon_coupon.coupon_id = salesrule.coupon_id",
                ['code']
            )
        ->where(
                'tieredcoupon_id = :tieredcoupon_id'
            );
        $bind = ['tieredcoupon_id' => (int)$tieredcoupon->getId()];

        return $this->getConnection()->fetchCol($select, $bind);
    }

    /**
     * Save sub coupons
     *
     * @param \Mexbs\Tieredcoupon\Model\Tieredcoupon $tieredcoupon
     * @return $this
     */
    protected function _saveSubCoupons($tieredcoupon)
    {
        $id = $tieredcoupon->getId();
        $subCouponIds = $tieredcoupon->getSubCouponIds();

        if ($subCouponIds === null) {
            return $this;
        }

        $oldSubCouponIds = $this->getSubCouponIds($tieredcoupon);

        $insert = array_diff($subCouponIds, $oldSubCouponIds);
        $delete = array_diff($oldSubCouponIds, $subCouponIds);

        $connection = $this->getConnection();

        if (!empty($delete)) {
            $cond = ['coupon_id IN(?)' => $delete, 'tieredcoupon_id=?' => $id];
            $connection->delete($this->getTable("mexbs_tieredcoupon_coupon"), $cond);
        }

        if (!empty($insert)) {
            $data = [];
            foreach ($insert as $subCouponId) {
                $data[] = [
                    'tieredcoupon_id' => (int)$id,
                    'coupon_id' => (int)$subCouponId
                ];
            }
            $connection->insertMultiple($this->getTable("mexbs_tieredcoupon_coupon"), $data);
        }
        return $this;
    }

    /**
     * Save sub coupons
     *
     * @param \Magento\Framework\DataObject $object
     * @return $this
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        $this->_saveSubCoupons($object);
        return parent::_afterSave($object);
    }
}