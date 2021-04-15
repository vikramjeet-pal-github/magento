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


class Addpromo extends Action
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
    
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        SubscriptionCustomerRepository $subscriptionCustomerRepository,
        SubscriptionPromoRepository $subscriptionPromoRepository,
        SubscriptionPromoFactory $subscriptionPromoFactory,
        PromoHelper $promoHelper,
        Session $authSession
    ){
        $this->resultJsonFactory = $resultJsonFactory;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->subscriptionPromoRepository = $subscriptionPromoRepository;
        $this->subscriptionPromoFactory = $subscriptionPromoFactory;
        $this->promoHelper = $promoHelper;
        $this->authSession = $authSession;
        parent::__construct($context);
    }

    public function execute() { 
        $result = $this->resultJsonFactory->create();
        if ($this->getRequest()->isAjax()) 
        {
            $params = $this->getRequest()->getParams();
            $adminUserId = $this->authSession->getUser()->getId();
            $isValidRequest = isset($params['subscriptionCustomerId']) && isset($params['value']);
            
            if($isValidRequest){
                //This value needs to be a salesrule Id or a coupon code
                $promoValue = $params['value'];
                $type = $params['type'];
                $subscriptionCustomerId = intval($params['subscriptionCustomerId']);
                try {
                    $subscriptionPromo = $this->generatePromo($promoValue, $type, $subscriptionCustomerId);
                    $response = [
                        'Status'=>'success', 
                        'subscriptionCustomerId' => $subscriptionCustomerId,
                        'subscriptionPromo' =>  [
                            'Id' => $subscriptionPromo->getId(),
                            'Coupon Code' => $subscriptionPromo->getCouponCode(),
                            'Used Status' => $subscriptionPromo->getUsedStatus() ? "Used" : "Active",
                            'Used At' => $subscriptionPromo->getUsedAt() ? 
                                $subscriptionPromo->getUsedAt() :
                                "N/A",
                            'Created At' => $subscriptionPromo->getCreatedAt()
                        ]];
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

    protected function generatePromo($promoValue, $type, $subscriptionCustomerId)
    {
        $subscriptionCustomer = $this->subscriptionCustomerRepository->getById($subscriptionCustomerId);
        $customerId = $subscriptionCustomer->getCustomerId();
        if($type == 'promo'){
            //$promoValue will be the sales rule id in this case
            $ruleIsValid = $this->promoHelper->ruleIdIsValid($promoValue);
            $couponCode = $this->promoHelper->generateSingleCouponCode($promoValue);

            $subscriptionPromo = $this->subscriptionPromoFactory->create();                    
            $subscriptionPromo->setSubscriptionCustomerId($subscriptionCustomerId)
                              ->setCouponCode($couponCode)
                              ->setCreatedAt(Carbon::now()->toDateTimeString());
            $this->subscriptionPromoRepository->save($subscriptionPromo);
            return $subscriptionPromo;
        } else if($type == 'coupon'){
            $couponIsValid = $this->promoHelper->couponCodeIsValid($promoValue, $customerId);
            $subscriptionPromo = $this->subscriptionPromoFactory->create();                    
            $subscriptionPromo->setSubscriptionCustomerId($subscriptionCustomerId)
                              ->setCouponCode($promoValue)
                              ->setCreatedAt(Carbon::now()->toDateTimeString());
            $this->subscriptionPromoRepository->save($subscriptionPromo);
            return $subscriptionPromo;
        } else {
            throw new \Exception('Promo type not valid');
        }
    }

}