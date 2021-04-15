<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */
namespace Vonnda\Subscription\Controller\Adminhtml\Subscriptioncustomer;

use Vonnda\Subscription\Model\ResourceModel\SubscriptionCustomer\CollectionFactory;

use Magento\Ui\Component\MassAction\Filter;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\Model\Session\Proxy as Session;


class BulkAssignNextOrder extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Vonnda_Subscription::manage';

    protected $collectionFactory;

    protected $filter;

    protected $session;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        Session $session,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->session = $session;
        $this->resultPageFactory = $resultPageFactory;
    }
    public function execute()
    {
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $subscriptionCustomerIds = [];
            foreach ($collection->getItems() as $item) {
                $subscriptionCustomerIds[] = $item->getId();
            }

            $this->session->setSubscriptionCustomerIds($subscriptionCustomerIds);

            $resultPage = $this->resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->prepend((__('Bulk Assign Next Order Date')));

            return $resultPage;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('vonnda_subscription/subscriptioncustomer/index');
        }
    }
}
