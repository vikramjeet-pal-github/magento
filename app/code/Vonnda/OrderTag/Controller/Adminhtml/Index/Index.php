<?php

namespace Vonnda\OrderTag\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;

/**
 * Class Index
 * @package Vonnda\OrderTag\Controller\Adminhtml\Index
 */
class Index extends \Magento\Backend\App\Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Framework\Event\Manager
     */
    protected $eventManager;

    /**
     * Index constructor.
     * @param Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\Event\Manager $eventManager
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->coreRegistry = $coreRegistry;
        $this->eventManager = $eventManager;
    }

    /**
     * @return $this
     */
    protected function _initAction()
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu('Vonnda_OrderTag::ordertags')->_addBreadcrumb(__('Order Tags'), __('Order Tags'));
        return $this;
    }

    /**
     * Tag initialization
     *
     * @return string tag id
     */
    protected function initTag()
    {
        $tagId = (int)$this->getRequest()->getParam('id');

        if ($tagId) {
            $this->coreRegistry->register('ordertag', $tagId);
        }

        return $tagId;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend((__('Order Tags')));

        return $resultPage;
    }
}
