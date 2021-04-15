<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */
namespace Vonnda\Subscription\Controller\Adminhtml\Subscriptioncustomer;

use Vonnda\Subscription\Model\SubscriptionCustomerRepository;
use Vonnda\Subscription\Helper\ValidationHelper;

use Carbon\Carbon;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\Model\Session\Proxy as Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Shipping\Model\Config as ShippingConfig;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;



class BulkAssignShippingMethodOverwritePost extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Vonnda_Subscription::manage';
    
    protected $session;

    protected $subscriptionCustomerRepository;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Shipping\Model\Config
     */
    protected $shippingConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    protected $customerRepository;

    protected $validationHelper;

    public function __construct(
        Context $context,
        Session $session,
        SubscriptionCustomerRepository $subscriptionCustomerRepository,
        ScopeConfigInterface $scopeConfig,
        ShippingConfig $shippingConfig,
        StoreManagerInterface $storeManager,
        CustomerRepositoryInterface $customerRepository,
        ValidationHelper $validationHelper
    ) {
        parent::__construct($context);
        $this->session = $session;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->scopeConfig = $scopeConfig;
        $this->shippingConfig = $shippingConfig;
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
        $this->validationHelper = $validationHelper;
    }
    public function execute()
    {
        try {
            $subscriptionIds = $this->session->getSubscriptionCustomerIds();
            $params = $this->getRequest()->getParams();
            $method = $params['shipping-method-overwrite'];
            $numOfSubscriptions = count($subscriptionIds);
            foreach($subscriptionIds as $subscriptionId){
                $subscriptionCustomer = $this->subscriptionCustomerRepository->getById($subscriptionId);
                if(!$this->validationHelper->shippingMethodIsValidForSubscription($subscriptionCustomer, $method)){
                    continue;
                }

                $shouldSetCostOverwrite = $method && isset($params['shipping-cost-overwrite']) && 
                    ($params['shipping-cost-overwrite'] || $this->isZeroNumber($params['shipping-cost-overwrite']));
                $subscriptionCustomer->setShippingMethodOverwrite($method ? $method : null);
                if($shouldSetCostOverwrite){
                    $subscriptionCustomer->setShippingCostOverwrite($params['shipping-cost-overwrite']);
                } else {
                    $subscriptionCustomer->setShippingCostOverwriteToNull();
                }
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

    public function isZeroNumber($field)
    {
        if($field === false){
            return false;
        }

        if($field === ""){
            return false;
        }

        if($field === null){
            return false;
        }

        $num = floatval($field);
        if($num == 0){
            return true;
        }

        return false;
    }

}
