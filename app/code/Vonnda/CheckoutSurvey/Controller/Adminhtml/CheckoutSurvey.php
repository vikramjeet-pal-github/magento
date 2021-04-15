<?php

namespace Vonnda\CheckoutSurvey\Controller\Adminhtml;

abstract class CheckoutSurvey extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Vonnda_CheckoutSurvey::top_level';

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry
    ) {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context);
    }

    /**
     * Init page
     *
     * @param \Magento\Backend\Model\View\Result\Page $resultPage
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function initPage($resultPage)
    {
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE)
            ->addBreadcrumb(__('Vonnda'), __('Vonnda'))
            ->addBreadcrumb(__('Checkout Survey'), __('Checkout Survey'));
        return $resultPage;
    }
}
