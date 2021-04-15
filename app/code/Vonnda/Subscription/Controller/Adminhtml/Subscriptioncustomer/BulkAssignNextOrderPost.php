<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */
namespace Vonnda\Subscription\Controller\Adminhtml\Subscriptioncustomer;

use Vonnda\Subscription\Model\SubscriptionCustomerRepository;

use Carbon\Carbon;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\Model\Session\Proxy as Session;


class BulkAssignNextOrderPost extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Vonnda_Subscription::manage';
    
    protected $session;

    protected $subscriptionCustomerRepository;

    public function __construct(
        Context $context,
        Session $session,
        SubscriptionCustomerRepository $subscriptionCustomerRepository
    ) {
        parent::__construct($context);
        $this->session = $session;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
    }
    public function execute()
    {
        try {
            $subscriptionIds = $this->session->getSubscriptionCustomerIds();
            $params = $this->getRequest()->getParams();
            $numOfSubscriptions = count($subscriptionIds);
            foreach($subscriptionIds as $subscriptionId){
                $subscriptionCustomer = $this->subscriptionCustomerRepository->getById($subscriptionId);
                
                $nextOrder = Carbon::createFromFormat("m/d/Y", $params['next-order'])->startOfDay()->toDateTimeString();
                $subscriptionCustomer->setNextOrder($nextOrder);
                $this->subscriptionCustomerRepository->save($subscriptionCustomer);
            }
            $this->messageManager->addSuccessMessage($numOfSubscriptions . " subscriptions modified.");
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('vonnda_subscription/subscriptioncustomer/index');
            return $resultRedirect;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('vonnda_subscription/subscriptioncustomer/index');
        }
    }
}
