<?php

namespace Vonnda\Subscription\Controller\Customer;

use Vonnda\Subscription\Model\SubscriptionCustomer;
use Vonnda\Subscription\Api\SubscriptionCustomerRepositoryInterface;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Message\ManagerInterface;

class DeleteCustomerAddress extends Action{

    protected $addressRepository;

    protected $resultJsonFactory;

    protected $subscriptionCustomerRepository;

    protected $session;

    protected $messageManager;

    /**
     * Search Criteria Builder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * 
     * Address Delete
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function __construct(
        SubscriptionCustomerRepositoryInterface $subscriptionCustomerRepository,
        AddressRepositoryInterface $addressRepository,
        JsonFactory $resultJsonFactory,
        Context $context,
        Session $session,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ManagerInterface $messageManager
    ){
        $this->session = $session;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->addressRepository = $addressRepository;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->messageManager = $messageManager;
        parent::__construct($context);   
    }


    public function execute()
    {
        $request = $this->getRequest();
        $params = $request->getParams();
        try{
            if (!$this->session->isLoggedIn()){
                throw new \Exception('Unauthorized');
            }

            $requestIsValid = isset($params['addressId']) && $params['addressId'];
            if(!$requestIsValid){
                throw new \Exception('Invalid request');
            }

            $customerId = $this->session->getCustomer()->getId();
            $addressCanBeDeleted = !$this->isAddressUsedOnSubscription($params['addressId'], $customerId);
            if(!$addressCanBeDeleted){
                throw new \Exception('Existing subscription');
            }

            $this->addressRepository->deleteById($params['addressId']);
            $this->messageManager->addSuccess( __('Thanks for the update.'));
            return  $this->resultJsonFactory->create()->setData(['status' => 'success', 'addressId' => $params['addressId']]);
        } catch(\Exception $e) {
            $result = $this->resultJsonFactory->create()->setData(['status' => 'error','message' => $e->getMessage()]);
            return $result;
        }
    }

    public function isAddressUsedOnSubscription($addressId, $customerId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('customer_id',$customerId,'eq')
            ->addFilter('shipping_address_id', $addressId, 'eq')
            ->addFilter('state', SubscriptionCustomer::ACTIVE_STATE, 'eq')
            ->create();
        $subcriptionList = $this->subscriptionCustomerRepository->getList($searchCriteria);
        if($subcriptionList->getTotalCount() > 0){
            return true;
        }
        return false;
    }
}