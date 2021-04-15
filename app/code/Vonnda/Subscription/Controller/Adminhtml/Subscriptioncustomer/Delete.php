<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Controller\Adminhtml\Subscriptioncustomer;

use Vonnda\Subscription\Model\SubscriptionCustomer;
use Magento\Backend\App\Action;

class Delete extends Action
{
     /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Vonnda_Subscription::manage';
    
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        if (!($subscriptionCustomer = $this->_objectManager->create(SubscriptionCustomer::class)->load($id))) {
            $this->messageManager->addError(__('Unable to proceed. Please, try again.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/index', array('_current' => true));
        }
        try{
            $subscriptionCustomer->delete();
            $this->messageManager->addSuccess(__('Your subscription has been deleted !'));
        } catch (Exception $e) {
            $this->messageManager->addError(__('Error while trying to delete subscription: '));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/index', array('_current' => true));
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/index', array('_current' => true));
    }
}