<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Block\Adminhtml\SubscriptionHistory;

use Vonnda\Subscription\Model\SubscriptionCustomerRepository;
use Vonnda\Subscription\Model\SubscriptionHistoryRepository;
use Vonnda\Subscription\Model\SubscriptionPaymentRepository;
use Vonnda\Subscription\Model\SubscriptionOrderRepository;
use Vonnda\Subscription\Model\SubscriptionPromoRepository;
use Vonnda\Subscription\Model\SubscriptionPlanRepository;
use Vonnda\Subscription\Helper\Logger;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;


class View extends Template
{
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
     * Subscription Payment Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionPaymentRepository $subscriptionPaymentRepository
     */
    protected $subscriptionPaymentRepository;

    /**
     * Subscription Order Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionOrderRepository $subscriptionOrderRepository
     */
    protected $subscriptionOrderRepository;

    /**
     * Subscription History Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionHistoryRepository $subscriptionHistoryRepository
     */
    protected $subscriptionHistoryRepository;

    /**
     * Subscription Plan Repository
     *
     * @var \Vonnda\Subscription\Model\SubscriptionPlanRepository $subscriptionPlanRepository
     */
    protected $subscriptionPlanRepository;

    /**
     * Vonnda Logger Helper
     *
     * @var \Vonnda\Subscription\Helper\Logger $logger
     */
    protected $logger;

    /**
     * Search Criteria Builder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    
    /**
     * Request Object
     *
     * @var \Magento\Framework\App\RequestInterface $request
     */
    protected $request;

    /**
     * Address Repository
     *
     * @var \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     */
    protected $addressRepository;

    /**
     * Backend Url
     *
     * @var \Magento\Backend\Model\UrlInterface $backendUrlInterface
     */
    protected $backendUrlInterface;

    /**
     * Customer Repository Interface
     *
     * @var \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    protected $customerRepository;

    /**
     * 
     * Subscription Customer Info Block Constructor
     * 
     * @param SubscriptionCustomerRepository $subscriptionCustomerRepository
     * @param SubscriptionPromoRepository $subscriptionPromoRepository
     * @param SubscriptionHistoryRepository $subscriptionHistoryRepository
     * @param SubscriptionPlanRepository $subscriptionPlanRepository
     * @param SubscriptionPaymentRepository $subscriptionPaymentRepository
     * @param SubscriptionOrderRepository $subscriptionOrderRepository
     * @param Logger $logger
     * @param Context $context
     * @param RequestInterface $request
     * @param AddressRepositoryInterface $addressRepository
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param UrlInterface $backendUrlInterface
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * 
     * 
     */
    public function __construct(
        SubscriptionCustomerRepository $subscriptionCustomerRepository,
        SubscriptionPromoRepository $subscriptionPromoRepository,
        SubscriptionHistoryRepository $subscriptionHistoryRepository,
        SubscriptionPlanRepository $subscriptionPlanRepository,
        SubscriptionPaymentRepository $subscriptionPaymentRepository,
        SubscriptionOrderRepository $subscriptionOrderRepository,
        Logger $logger,
        Context $context,
        RequestInterface $request,
        AddressRepositoryInterface $addressRepository,
        CustomerRepositoryInterface $customerRepository,
        UrlInterface $backendUrlInterface,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ){
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->subscriptionPromoRepository = $subscriptionPromoRepository;
        $this->subscriptionHistoryRepository = $subscriptionHistoryRepository;
        $this->subscriptionPlanRepository = $subscriptionPlanRepository;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
        $this->subscriptionOrderRepository = $subscriptionOrderRepository;
        $this->logger = $logger;
        $this->request = $request;
        $this->addressRepository = $addressRepository;
        $this->customerRepository = $customerRepository;
        $this->backendUrlInterface = $backendUrlInterface;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        parent::__construct($context);
	}

    public function getSubscriptionHistory()
    {
        $id = $this->request->getParam('id');
        if($id){
            try {
                return $this->subscriptionHistoryRepository->getById($id);
            } catch(\Exception $e){
                return false;
            }
        } else {
            return false;
        }
    }

    public function getBackUrl()
    {
        return $this->backendUrlInterface->getUrl("vonnda_subscription/subscriptionhistory/index");
    }

    public function formatObject($object)
    {
        return $this->_formatJson(json_encode($object), true);
    }

    /**
	 * Formats a JSON string for pretty printing
	 *
	 * @param string $json The JSON to make pretty
	 * @param bool $html Insert nonbreaking spaces and <br />s for tabs and linebreaks
	 * @return string The prettified output
	 * @author Jay Roberts
	 */
    protected function _formatJson($json, $html = false) {
		$tabcount = 0; 
		$result = ''; 
		$inquote = false; 
		$ignorenext = false; 
		if ($html) { 
		    $tab = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"; 
		    $newline = "<br/>"; 
		} else { 
		    $tab = "\t"; 
		    $newline = "\n"; 
		} 
		for($i = 0; $i < strlen($json); $i++) { 
		    $char = $json[$i]; 
		    if ($ignorenext) { 
		        $result .= $char; 
		        $ignorenext = false; 
		    } else { 
		        switch($char) { 
		            case '{': 
		                $tabcount++; 
		                $result .= $char . $newline . str_repeat($tab, $tabcount); 
		                break; 
		            case '}': 
		                $tabcount--; 
		                $result = trim($result) . $newline . str_repeat($tab, $tabcount) . $char; 
		                break; 
		            case ',': 
		                $result .= $char . $newline . str_repeat($tab, $tabcount); 
		                break; 
		            case '"': 
		                $inquote = !$inquote; 
		                $result .= $char; 
		                break; 
		            case '\\': 
		                if ($inquote) $ignorenext = true; 
		                $result .= $char; 
		                break; 
		            default: 
		                $result .= $char; 
		        } 
		    } 
		} 
		return $result; 
	}

}