<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Controller\Adminhtml\Subscriptionplan;

use Vonnda\Subscription\Model\SubscriptionPlan;
use Magento\Backend\App\Action;

class Delete extends Action
{
    /**
     * Authorization level of a basic admin session
     */
	const ADMIN_RESOURCE = 'Vonnda_Subscription::manage_plans';

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        if (!($subscriptionPlan = $this->_objectManager->create(SubscriptionPlan::class)->load($id))) {
            $this->messageManager->addError(__('Unable to proceed. Please, try again.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/index', array('_current' => true));
        }
        try{
            $subscriptionPlan->delete();
            $this->messageManager->addSuccess(__('Your tier has been deleted !'));
        } catch (Exception $e) {
            $this->messageManager->addError(__('Error while trying to delete tier: '));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/index', array('_current' => true));
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/index', array('_current' => true));
    }
}