<?php
/**
 * Copyright © 2020 Grazitti . All rights reserved.
 */

namespace Grazitti\Maginate\Controller\Adminhtml\Logs;

class Index extends \Magento\Backend\App\Action
{
    protected $resultPageFactory = false;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Grazitti_Maginate::mktmenu');
        $resultPage->getConfig()->getTitle()->prepend((__('Manage Logs')));
        $resultPage->addBreadcrumb(__('Logs'), __('Logs'));
        return $resultPage;
    }
}
