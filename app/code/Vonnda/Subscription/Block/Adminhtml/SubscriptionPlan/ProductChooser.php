<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Block\Adminhtml\SubscriptionPlan;

use Vonnda\Subscription\Model\SubscriptionProductRepository;
use Vonnda\Subscription\Model\SubscriptionPlanRepository;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use function GuzzleHttp\json_encode;

class ProductChooser extends Template
{
    /**
     * Request Object
     *
     * @var \Magento\Framework\App\RequestInterface $request
     */
    protected $request;

    /**
     * Product Repository
     *
     * @var \Magento\Catalog\Model\ProductRepository $productRepository
     */
    protected $productRepository;

    /**
     * Subscription Plan Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionPlanRepository $subscriptionPlanRepository
     */
    protected $subscriptionPlanRepository;

    /**
     * Subscription Product Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionProductRepository $subscriptionProductRepository
     */
    protected $subscriptionProductRepository;

    /**
     * Search Criteria Builder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;


    /**
     * 
     * Subscription Plan Product Chooser
     * 
     * @param Context $context
     * @param RequestInterface $request
     * 
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        ProductRepository $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SubscriptionPlanRepository $subscriptionPlanRepository,
        SubscriptionProductRepository $subscriptionProductRepository
    ){
        $this->request = $request;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->subscriptionPlanRepository = $subscriptionPlanRepository;
        $this->subscriptionProductRepository = $subscriptionProductRepository;
        parent::__construct($context);
	}

    public function getSubscriptionProductsForPlan()
    {
        $id = $this->request->getParam('id');
        if($id){
            try {
                $searchCriteria = $this->searchCriteriaBuilder
                                       ->addFilter('subscription_plan_id',$id,'eq')
                                       ->create();
                $subscriptionProductList = $this->subscriptionProductRepository->getList($searchCriteria);
                return $subscriptionProductList->getItems();
            } catch(\Exception $e){
                return false;
            }
        } else {
            return false;
        }
    }

    //TODO - add some relevant attribute filters to only show relevant products
    public function getAvailableProductsForSelect()
    {
        try {
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $productList = $this->productRepository->getList($searchCriteria);
            if(!$productList->getTotalCount()){
                return null;
            }
            return $productList->getItems();
        } catch(\Exception $e){
            return null;
        }
    }

    public function getSubscriptionProductsForPlanJSON()
    {
        $subscriptionProductList = $this->getSubscriptionProductsForPlan();
        $returnArr=[];
        if($subscriptionProductList){
            foreach($subscriptionProductList as $subscriptionProduct){
                try {
                    $product = $this->productRepository->getById($subscriptionProduct->getProductId());
                    $returnArr[] = [
                        "name" => $product->getName(),
                        "sku" => $product->getSku(),
                        "id" => $product->getId(),
                        "qty" => $subscriptionProduct->getQty(),
                        "price_override" => $subscriptionProduct->getPriceOverride()
                    ];
                } catch(\Exception $e){

                }
            }
        }
        return json_encode($returnArr);
    }

}