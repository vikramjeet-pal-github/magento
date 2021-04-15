<?php
namespace Mexbs\Tieredcoupon\Block\Adminhtml\Coupon\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class DeleteButton
 */
class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        $data = [];
        $couponId = $this->getTieredcouponId();
        if ($couponId && $this->canRender('delete')) {
            $data = [
                'label' => __('Delete'),
                'class' => 'delete',
                'on_click' => 'deleteConfirm(\'' . __(
                    'Are you sure you want to delete this?'
                ) . '\', \'' . $this->urlBuilder->getUrl('tieredcoupon/coupon/delete', ['id' => $couponId]) . '\')',
                'sort_order' => 20,
            ];
        }
        return $data;
    }
}
