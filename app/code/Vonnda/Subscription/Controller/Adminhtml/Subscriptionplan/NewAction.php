<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Controller\Adminhtml\Subscriptionplan;

use Vonnda\Subscription\Model\SubscriptionPlanFactory;
use Vonnda\Subscription\Model\SubscriptionProductFactory;
use Vonnda\Subscription\Helper\TimeDateHelper;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;


class NewAction extends Action
{
     /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Vonnda_Subscription::manage_plans';
    
    protected $managerInterface;

    protected $subscriptionPlanFactory;

    protected $subscriptionProductFactory;

    protected $timeDateHelper;
    
    public function __construct(
        Context $context,
        ManagerInterface $managerInterface,
        SubscriptionPlanFactory $subscriptionPlanFactory,
        SubscriptionProductFactory $subscriptionProductFactory,
        TimeDateHelper $timeDateHelper
    ){
        $this->managerInterface = $managerInterface;
        $this->subscriptionPlanFactory = $subscriptionPlanFactory;
        $this->subscriptionProductFactory = $subscriptionProductFactory;
        $this->timeDateHelper = $timeDateHelper;
        parent::__construct($context);
    }
    
    /**
     * Edit A Contact Page
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->renderLayout();
        $resultRedirect = $this->resultRedirectFactory->create();

        $subscriptionPlanData = $this->getRequest()->getParam('subscriptionPlan');
        if(is_array($subscriptionPlanData)) {
            $subscriptionPlan = $this->subscriptionPlanFactory->create();
            
            $duration = $subscriptionPlanData['duration'];
            if(!$duration){//Zero or blank will set to null
                $subscriptionPlanData['duration'] = null;
            } else {
                if(intval($duration) == 0){//Any other odd string will be set to zero
                    $this->messageManager->addError(__("Duration invalid - must be an integer or empty"));
                    return $resultRedirect->setRefererOrBaseUrl();
                }
            }
            
            $subscriptionPlan->setData($subscriptionPlanData)->save();
            $this->handleSubscriptionProducts($subscriptionPlan->getId(), 
                                              $subscriptionPlanData['subscription_products']);

            $this->messageManager->addSuccess(__("Tier added"));
            return $resultRedirect->setPath('*/*/index');
        }
    }

    public function handleSubscriptionProducts($subscriptionPlanId, $subscriptionPlanData)
    {
        $newProductArr = $this->simplifyArray("id", json_decode($subscriptionPlanData, true));
        
        if(count($newProductArr) > 0){
            foreach($newProductArr as $id=>$fields){
                try {
                    $newProduct = $this->subscriptionProductFactory->create();
                    $newProduct->setProductId($id)
                               ->setSubscriptionPlanId($subscriptionPlanId)
                               ->setQty($fields['qty'])
                               ->setPriceOverride($this->validatePriceOverride($fields['price_override']))
                               ->save();
                } catch(\Exception $e){
                    $message = $e->getMessage();
                }
            }
        }
        
    }
    public function simplifyArray($fieldNameOne, $array)
    {
        $returnArr = [];
        if($array === null){
            return $returnArr;
        }

        foreach($array as $item){
            $fieldValue = $item[$fieldNameOne];
            unset($item[$fieldNameOne]);
            $returnArr[$fieldValue] = $item;
        }
        return $returnArr;
    }

    //TODO - move to model
    protected function validatePriceOverride($value)
    {
        if($value === ''){
            return null;
        } else {
            return (float)$value;
        }
    }

}