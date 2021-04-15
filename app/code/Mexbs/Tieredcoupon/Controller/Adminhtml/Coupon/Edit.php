<?php
namespace Mexbs\Tieredcoupon\Controller\Adminhtml\Coupon;

class Edit extends \Mexbs\Tieredcoupon\Controller\Adminhtml\Coupon\Tieredcoupon
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->_coreRegistry = $coreRegistry;
        $this->_fileFactory = $fileFactory;
        $this->_dateFilter = $dateFilter;
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Initiate action
     *
     * @return $this
     */
    protected function _initAction()
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu('Mexbs_Tieredcoupon::grid')->_addBreadcrumb(__('Tiered Coupons'), __('Tiered Coupons'));
        return $this;
    }

    /**
     * Tiered coupon edit action
     *
     * @return void
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->_objectManager->create(\Mexbs\Tieredcoupon\Model\Tieredcoupon::class);

        $this->_coreRegistry->register(\Mexbs\Tieredcoupon\Model\RegistryConstants::CURRENT_COUPON, $model);

        $resultPage = $this->resultPageFactory->create();
        if ($id) {
            $model->load($id);
            if (!$model->getTieredcouponId()) {
                $this->messageManager->addErrorMessage(__('This coupon no longer exists.'));
                $this->_redirect('tieredcoupon/*');
                return;
            }
        }

        // set entered data if was error when we do save
        $data = $this->_objectManager->get(\Magento\Backend\Model\Session::class)->getPageData(true);
        if (!empty($data)) {
            $model->addData($data);
        }

        $this->_initAction();

        $this->_addBreadcrumb($id ? __('Edit Coupon') : __('New Coupon'), $id ? __('Edit Coupon') : __('New Coupon'));

        $this->_view->getPage()->getConfig()->getTitle()->prepend(
            $id ? $model->getName() : __('New Coupon')
        );
        $this->_view->renderLayout();
    }
}
