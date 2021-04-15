<?php
namespace Mexbs\Tieredcoupon\Controller\Adminhtml\Coupon;

class Save extends \Mexbs\Tieredcoupon\Controller\Adminhtml\Coupon\Tieredcoupon
{
    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    protected $layoutFactory;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\LayoutFactory $layoutFactory
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->layoutFactory = $layoutFactory;
    }

    /**
     * Tiered coupon save
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $tieredcoupon = $this->_initTieredcoupon();

        if (!$tieredcoupon) {
            return $resultRedirect->setPath('tieredcoupon/*/', ['_current' => true, 'id' => null]);
        }

        $data['general'] = $this->getRequest()->getPostValue();
        $tieredcouponPostData = $data['general'];

        $isNewTieredcoupon = !isset($tieredcouponPostData['entity_id']);

        if ($tieredcouponPostData) {
            $tieredcoupon->addData($tieredcouponPostData);

            if (isset($tieredcouponPostData['sub_coupon_ids'])
                && is_string($tieredcouponPostData['sub_coupon_ids'])
            ) {
                $subCouponIds = array_filter
                    (
                        json_decode($tieredcouponPostData['sub_coupon_ids'], 1),
                        function($subCouponId){
                            return is_numeric($subCouponId);
                        }
                    );
                $tieredcoupon->setSubCouponIds($subCouponIds);
            }

            try {
                $tieredcoupon->save();
                $this->messageManager->addSuccessMessage(__('You saved the tiered coupon.'));
            } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
                $this->_getSession()->setTieredcouponData($tieredcouponPostData);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
                $this->_getSession()->setTieredcouponData($tieredcouponPostData);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Something went wrong while saving the tiered coupon.'));
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
                $this->_getSession()->setTieredcouponData($tieredcouponPostData);
            }
        }

        $hasError = (bool)$this->messageManager->getMessages()->getCountByType(
            \Magento\Framework\Message\MessageInterface::TYPE_ERROR
        );

        if ($this->getRequest()->getPost('return_session_messages_only')) {
            $tieredcoupon->load($tieredcoupon->getId());
            /** @var $block \Magento\Framework\View\Element\Messages */
            $block = $this->layoutFactory->create()->getMessagesBlock();
            $block->setMessages($this->messageManager->getMessages(true));

            /** @var \Magento\Framework\Controller\Result\Json $resultJson */
            $resultJson = $this->resultJsonFactory->create();
            return $resultJson->setData(
                [
                    'messages' => $block->getGroupedHtml(),
                    'error' => $hasError,
                    'tieredcoupon' => $tieredcoupon->toArray(),
                ]
            );
        }

        if ($this->getRequest()->getParam('back')) {
            $this->_redirect('tieredcoupon/*/edit', ['id' => $tieredcoupon->getId()]);
            return;
        }
        $this->_redirect('tieredcoupon/coupon/grid');
    }
}
