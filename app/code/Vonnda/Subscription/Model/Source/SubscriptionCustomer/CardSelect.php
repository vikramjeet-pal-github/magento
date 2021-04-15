<?php

namespace Vonnda\Subscription\Model\Source\SubscriptionCustomer;

use Vonnda\Subscription\Model\SubscriptionCustomerRepository;
use Vonnda\Subscription\Helper\StripeHelper;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;


class CardSelect implements ArrayInterface
{
    protected $searchCriteriaBuilder;

    protected $customerRepositoryInterface;

    protected $subscriptionCustomerRepository;

    protected $requestInterface;

    protected $stripeHelper;

    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CustomerRepositoryInterface $customerRepositoryInterface,
        SubscriptionCustomerRepository $subscriptionCustomerRepository,
        RequestInterface $requestInterface,
        StripeHelper $stripeHelper
    ){
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->requestInterface = $requestInterface;
        $this->stripeHelper = $stripeHelper;
    }

    public function toOptionArray()
    {
        $dataArray = [];

        $subscriptionCustomerId = $this->requestInterface->getParam("id");
        if($subscriptionCustomerId){
            try {
                $subscriptionCustomer = $this->subscriptionCustomerRepository->getById($subscriptionCustomerId);
                $cards = $this->stripeHelper->getAllCustomerCards($subscriptionCustomer->getCustomerId());
                
                $dataArray[] = ['label' => "No card chosen", 'value' => ""];
                foreach($cards as $card){
                    $dataArray[] = [
                        'value' => $card->id,
                        'label' => $card->brand . " " . $card->last4 . " " . $card->exp_month . "/" . $card->exp_year
                    ];
                }

            } catch(\Exception $e){

            }
        } else {
            $dataArray[] = ['value' => '', 'label' => __("Choose customer first")];
        }

        return $dataArray;
    }

}