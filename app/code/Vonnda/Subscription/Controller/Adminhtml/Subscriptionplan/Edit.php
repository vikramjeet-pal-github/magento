<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Controller\Adminhtml\Subscriptionplan;

use Vonnda\Subscription\Model\SubscriptionPlanFactory;
use Vonnda\Subscription\Model\SubscriptionProductRepository;
use Vonnda\Subscription\Model\SubscriptionProductFactory;
use Vonnda\Subscription\Helper\TimeDateHelper;

use Magento\Backend\App\Action;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Message\ManagerInterface;
use Magento\Backend\App\Action\Context;


class Edit extends Action
{
     /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Vonnda_Subscription::manage_plans';
    
    protected $managerInterface;

    protected $searchCriteriaBuilder;

    protected $subscriptionPlanFactory;

    protected $subscriptionProductFactory;

    protected $subscriptionProductRepository;

    protected $timeDateHelper;
    
    public function __construct(
        Context $context,
        ManagerInterface $managerInterface,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SubscriptionPlanFactory $subscriptionPlanFactory,
        SubscriptionProductFactory $subscriptionProductFactory,
        SubscriptionProductRepository $subscriptionProductRepository,
        TimeDateHelper $timeDateHelper
    ){
        $this->managerInterface = $managerInterface;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->subscriptionPlanFactory = $subscriptionPlanFactory;
        $this->subscriptionProductFactory = $subscriptionProductFactory;
        $this->subscriptionProductRepository = $subscriptionProductRepository;
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

            $subscriptionPlanData['updated_at'] = date_create();
            $subscriptionPlan->setData($subscriptionPlanData)->save();
            $this->handleSubscriptionProducts($subscriptionPlanData['id'], 
                                              $subscriptionPlanData['subscription_products']);

            $this->messageManager->addSuccess(__("Tier updated"));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/index');
        }
    }

    public function handleSubscriptionProducts($subscriptionPlanId, $subscriptionPlanData)
    {
        $searchCriteria = $this->searchCriteriaBuilder
                               ->addFilter('subscription_plan_id',$subscriptionPlanId,'eq')
                               ->create();
        $initialSubscriptionProducts = $this->subscriptionProductRepository->getList($searchCriteria);
        
        $initialProductArr = [];
        $productIdMap = [];//To not search later on
        foreach($initialSubscriptionProducts->getItems() as $subProduct){
            $initialProductArr[$subProduct->getProductId()]['qty'] = (int)$subProduct->getQty();
            $initialProductArr[$subProduct->getProductId()]['price_override'] = (float)$subProduct->getPriceOverride();
            $productIdMap[$subProduct->getProductId()] = $subProduct->getId();
        }

        $newProductArr = $this->simplifyArray("id", json_decode($subscriptionPlanData, true));
        $productArrays = $this->sortArray($initialProductArr, $newProductArr);

        //Create new ones
        foreach($productArrays['add'] as $id=>$fields){
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

        //Remove old ones - id is the productId
        foreach($productArrays['remove'] as $id=>$fields){
            try {
                $toDelete = $this->subscriptionProductRepository->getById($productIdMap[$id]);
                $this->subscriptionProductRepository->delete($toDelete);
            } catch(\Exception $e){
                $message = $e->getMessage();
            }
        }

        //Update existing - id is the productId
        foreach($productArrays['update'] as $id=>$fields){
            try {
                $toUpdate = $this->subscriptionProductRepository->getById($productIdMap[$id]);
                $toUpdate->setQty($fields['qty'])->setPriceOverride($this->validatePriceOverride($fields['price_override']));
                $this->subscriptionProductRepository->save($toUpdate);
            } catch(\Exception $e){
                $message = $e->getMessage();
            }
        }

    }

    public function simplifyArray($fieldNameOne, $array)
    {
        $returnArr = [];
        foreach($array as $item){
            $fieldValue = $item[$fieldNameOne];
            unset($item[$fieldNameOne]);
            $returnArr[$fieldValue] = $item;
        }
        return $returnArr;
    }

    public function sortArray($initialProductArr, $newProductArr)
    {
        $returnArr = [
            "update" => [],
            "add" => [],
            "remove" => []
        ];

        //$newProductArr = get_object_vars($newProductArr);
        foreach($newProductArr as $key=>$value){
            if(!isset($initialProductArr[$key])){
                $returnArr['add'][$key] = $value;
            } else if($newProductArr[$key] != $initialProductArr[$key]){
                if($newProductArr[$key] == 0){
                    $returnArr['remove'][$key] = $value;
                } else {
                    $returnArr['update'][$key] = $value;
                }
            }
        }

        foreach($initialProductArr as $key=>$value){
            if(!isset($newProductArr[$key])){
                $returnArr['remove'][$key] = $value;
            }
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