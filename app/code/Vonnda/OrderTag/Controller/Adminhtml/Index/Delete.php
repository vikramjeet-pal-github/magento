<?php

namespace Vonnda\OrderTag\Controller\Adminhtml\Index;

/**
 * Class Delete
 * @package Vonnda\OrderTag\Controller\Adminhtml\Index
 */
class Delete extends Index
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page|void
     */
    public function execute()
    {
        $tagId = $this->initTag();
        if ($tagId) {
            try {
                $model = $this->_objectManager->create('Vonnda\OrderTag\Model\OrderTag');
                $model->load($tagId);
                $model->delete();
                $this->messageManager->addSuccess(__('You deleted the tag.'));
                $this->_redirect('ordertag/*/');
                return;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('We can\'t delete tag right now. Please review the log and try again.')
                );
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $this->_redirect('ordertag/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                return;
            }
        }
        $this->messageManager->addError(__('We can\'t find a tag to delete.'));
        $this->_redirect('ordertag/*/');
    }
}
