<?php 

namespace Vonnda\Subscription\Controller\Adminhtml\Subscriptioncustomer;  

use Vonnda\Subscription\Model\SubscriptionCustomerRepository;
use Vonnda\Subscription\Model\SubscriptionPromoRepository;
use Vonnda\Subscription\Model\SubscriptionPromoFactory;
use Vonnda\Subscription\Helper\PromoHelper;

use Carbon\Carbon;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\SalesRule\Api\CouponRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;


class Deletepromo extends Action 
{
     /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Vonnda_Subscription::manage';

    protected $resultJsonFactory;

    /**
     * Subscription Customer Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionCustomerRepository $subscriptionCustomerRepository
     */
    protected $subscriptionCustomerRepository;

     /**
     * Subscription Promo Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionPromoRepository $subscriptionPromoRepository
     */
    protected $subscriptionPromoRepository;

    /**
     * Subscription Promo Factory
     *
     * @var \Vonnda\Subscription\Model\SubscriptionPromoFactory $subscriptionPromoFactory
     */
    protected $subscriptionPromoFactory;

    /**
     * Promo Helper
     *
     * @var \Vonnda\Subscription\Helper\promoHelper $promoHelper
     */
    protected $promoHelper;

    /**
     * Auth Session
     *
     * @var \Magento\Backend\Model\Auth\Session $authSession
     */
    protected $authSession;

    /**
     * Search Criteria Builder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Coupon Repository
     *
     * @var \Magento\SalesRule\Api\CouponRepositoryInterface $couponRepository
     */
    protected $couponRepository;
    
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        SubscriptionCustomerRepository $subscriptionCustomerRepository,
        SubscriptionPromoRepository $subscriptionPromoRepository,
        SubscriptionPromoFactory $subscriptionPromoFactory,
        PromoHelper $promoHelper,
        Session $authSession, 
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CouponRepositoryInterface $couponRepository
    ){
        $this->resultJsonFactory = $resultJsonFactory;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->subscriptionPromoRepository = $subscriptionPromoRepository;
        $this->subscriptionPromoFactory = $subscriptionPromoFactory;
        $this->promoHelper = $promoHelper;
        $this->authSession = $authSession;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->couponRepository = $couponRepository;
        parent::__construct($context);
    }

    public function execute() { 
        $result = $this->resultJsonFactory->create();
        if ($this->getRequest()->isAjax()) 
        {
            $params = $this->getRequest()->getParams();
            $adminUserId = $this->authSession->getUser()->getId();
            $isValidRequest = isset($params['subscriptionCustomerId']) && isset($params['subscriptionPromoId']);
            
            if($isValidRequest && $adminUserId){
                $subscriptionCustomerId = intval($params['subscriptionCustomerId']);
                $subscriptionPromoId = intval($params['subscriptionPromoId']);
                
                try {
                    $deletedPromoId = $this->deleteSubscriptionPromo($subscriptionPromoId);
                    $response = [
                        'Status'=>'success', 
                        'subscriptionCustomerId' => $subscriptionCustomerId,
                        'subscriptionPromo' =>  ['Id' => $deletedPromoId]
                    ];
                } catch(\Exception $e){
                    $response = [
                        'Status'=>'error', 
                        'message' => $e->getMessage()];
                }
                return $result->setData($response);
            } else {
                $response = [
                    'Status'=>'error', 
                    'message' => 'Improper request'];
                return $result->setData($response);

            }
        }
    } 

    protected function deleteSubscriptionPromo(int $subscriptionPromoId)
    {
        $subscriptionPromo = $this->subscriptionPromoRepository->getById($subscriptionPromoId);

        $searchCriteria = $this->searchCriteriaBuilder
                               ->addFilter('code', $subscriptionPromo->getCouponCode(),'eq')
                               ->create();
        // $couponList = $this->couponRepository->getList($searchCriteria);
        // foreach($couponList->getItems() as $coupon){
        //     $this->couponRepository->deleteById($coupon->getId());
        //     break;
        // }

        $this->subscriptionPromoRepository->delete($subscriptionPromo);
        return $subscriptionPromoId;
    }
}