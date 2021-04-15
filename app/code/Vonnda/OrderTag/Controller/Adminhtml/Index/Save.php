<?php

namespace Vonnda\OrderTag\Controller\Adminhtml\Index;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Save extends Index implements HttpPostActionInterface
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $this->eventManager->dispatch('save_tag_unique_frontend_default');

        if ($this->getRequest()->getPostValue('ordertag')) {
            try {
                $model = $this->_objectManager->create('Vonnda\OrderTag\Model\OrderTag');
                $data = $this->getRequest()->getPostValue('ordertag');

                $inputFilter = new \Zend_Filter_Input(
                    [],
                    [],
                    $data
                );
                $data = $inputFilter->getUnescaped();
                $id = $this->getRequest()->getParam('id');
                if ($id) {
                    $model->load($id);
                    if ($id != $model->getId()) {
                        throw new \Magento\Framework\Exception\LocalizedException(__('The wrong item is specified.'));
                    }
                }
                $model->setData($data);
                $session = $this->_objectManager->get('Magento\Backend\Model\Session');
                $session->setPageData($model->getData());
                $model->save();
                $this->coreRegistry->register('ordertag', $id);
                $this->messageManager->addSuccess(__('You saved the new tag.'));
                $session->setPageData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('ordertag/*/edit', ['id' => $model->getId()]);
                    return;
                }
                $this->_redirect('ordertag/*/');
                return;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $id = (int)$this->getRequest()->getParam('id');
                if (!empty($id)) {
                    $this->_redirect('ordertag/*/edit', ['id' => $id]);
                } else {
                    $this->_redirect('ordertag/*/new');
                }
                return;
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('Something went wrong while saving the item data. Please review the error log.')
                );
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $this->_objectManager->get('Magento\Backend\Model\Session')->setPageData($data);
                $this->_redirect('ordertag/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                return;
            }
        }
        $this->_redirect('ordertag/*/');
    }
}
