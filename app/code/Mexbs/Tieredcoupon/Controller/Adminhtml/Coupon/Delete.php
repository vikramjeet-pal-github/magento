<?php
namespace Mexbs\Tieredcoupon\Controller\Adminhtml\Coupon;

class Delete extends \Mexbs\Tieredcoupon\Controller\Adminhtml\Coupon\Tieredcoupon
{
    /**
     * Delete promo quote action
     *
     * @return void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            try {
                $tieredcoupon = $this->_initTieredcoupon();
                $tieredcoupon->delete();
                $this->messageManager->addSuccessMessage(__('You deleted the tiered coupon.'));
                $this->_redirect('tieredcoupon/*/grid');
                return;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('We can\'t delete the tiered coupon right now. Please review the log and try again.')
                );
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
                $this->_redirect('tieredcoupon/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                return;
            }
        }
        $this->messageManager->addErrorMessage(__('We can\'t find a tiered coupon to delete.'));
        $this->_redirect('tieredcoupon/*/grid');
    }
}
