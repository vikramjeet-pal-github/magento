<?php

namespace Vonnda\OrderTag\Controller\Adminhtml\Index;

/**
 * Class Edit
 * @package Vonnda\OrderTag\Controller\Adminhtml\Index
 */
class Edit extends Index
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page|void
     */
    public function execute()
    {
        $tagId = $this->initTag();

        $model = $this->_objectManager->create('Vonnda\OrderTag\Model\OrderTag');

        if ($tagId) {
            $model->load($tagId);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This item no longer exists.'));
                $this->_redirect('ordertag/*');
                return;
            }
        }
        // set entered data if was error when we do save
        $data = $this->_objectManager->get('Magento\Backend\Model\Session')->getPageData(true);
        if (!empty($data)) {
            $model->addData($data);
        }
        $resultPage = $this->resultPageFactory->create();

        if ($tagId) {
            $resultPage->getConfig()->getTitle()->prepend(ucfirst($model->getLabel()));
        } else {
            $resultPage->getConfig()->getTitle()->prepend(__('New Order Tag'));
        }

        return $resultPage;
    }
}
