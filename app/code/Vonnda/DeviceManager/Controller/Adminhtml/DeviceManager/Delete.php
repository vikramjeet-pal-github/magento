<?php

namespace Vonnda\DeviceManager\Controller\Adminhtml\DeviceManager;

class Delete extends \Vonnda\DeviceManager\Controller\Adminhtml\DeviceManager
{
     /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Vonnda_DeviceManager::manage_devices';

    /**
     * Delete action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        // check if we know what should be deleted
        $id = $this->getRequest()->getParam('entity_id');
        if ($id) {
            try {
                // init model and delete
                $model = $this->_objectManager->create(\Vonnda\DeviceManager\Model\DeviceManager::class);
                $model->load($id);
                $model->delete();
                // display success message
                $this->messageManager->addSuccessMessage(__('You deleted the Device.'));
                // go to grid
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addErrorMessage($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath('*/*/edit', ['entity_id' => $id]);
            }
        }
        // display error message
        $this->messageManager->addErrorMessage(__('We can\'t find a Device to delete.'));
        // go to grid
        return $resultRedirect->setPath('*/*/');
    }
}
