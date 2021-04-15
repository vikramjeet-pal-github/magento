<?php
/**
 * Copyright © 2020 Grazitti . All rights reserved.
 */

namespace Grazitti\Maginate\Controller\Adminhtml\Logs;

use Grazitti\Maginate\Model\Logs as mlogs;
use Magento\Backend\Model\Session as mSession;

class Edit extends \Grazitti\Maginate\Controller\Adminhtml\Logs
{
    protected $_mLogs;
    protected $_mSession;
    /**
     * @param mlogs $_mlogs
     * @param mSession $_mSession
     */
    public function __construct(mlogs $mlogs, mSession $mSession)
    {
        $this->_mLogs = $mlogs;
        $this->_mSession = $mSession;
    }
    
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->_mLogs;
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This item no longer exists.'));
                $this->_redirect('grazitti_maginate/*');
                return;
            }
        }
        // set entered data if was error when we do save
        $data = $this->_mSession->getPageData(true);
        if (!empty($data)) {
            $model->addData($data);
        }
        $this->_coreRegistry->register('current_grazitti_maginate_Logs', $model);
        $this->_initAction();
        $this->_view->getLayout()->getBlock('Logs_Logs_edit');
        $this->_view->renderLayout();
    }
}
