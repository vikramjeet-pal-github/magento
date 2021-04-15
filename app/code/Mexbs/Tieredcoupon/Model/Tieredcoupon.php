<?php
namespace Mexbs\Tieredcoupon\Model;

use Magento\Framework\Model\AbstractModel;

class Tieredcoupon  extends AbstractModel
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Mexbs\Tieredcoupon\Model\ResourceModel\Tieredcoupon');
    }

    /**
     * Retrieve array of sub coupon ids
     *
     * @return array
     */
    public function getSubCouponIds()
    {
        if (!$this->getId()) {
            return [];
        }

        $array = $this->getData('sub_coupon_ids');
        if ($array === null) {
            $array = $this->getResource()->getSubCouponIds($this);
            $this->setData('sub_coupon_ids', $array);
        }
        return $array;
    }

    /**
     * Retrieve array of sub coupon codes
     *
     * @return array
     */
    public function getSubCouponCodes()
    {
        if (!$this->getId()) {
            return [];
        }

        return $this->getResource()->getSubCouponCodes($this);
    }
}