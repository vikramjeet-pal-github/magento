<?php
/**
 * Copyright © 2020 Graziiti. All rights reserved.
 */
namespace Grazitti\Maginate\Controller\Adminhtml\Order;

use Magento\Framework\Stdlib\DateTime;
use Grazitti\Maginate\Model\Orderapi;
use Magento\Framework\App\Config\ScopeConfigInterface;


use \Magento\Customer\Model\Customer;
use \Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use \Magento\Backend\App\Action\Context;
use \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use \Magento\Eav\Model\Entity\Collection\AbstractCollection;
use \Magento\Ui\Component\MassAction\Filter;
use \Magento\Customer\Api\CustomerRepositoryInterface;
use \Magento\Framework\Controller\ResultFactory;


class Masssync extends \Magento\Backend\App\Action
{
    /**
     * Masssync Action for Mass Sync of customer with Marketo
     * @return Void
     * */
    protected $_objectManager;
    protected $_api;
    protected $scopeConfig;
    protected $_leadIntegration;
    const XML_PATH_LEAD_INTEGRATION = 'grazitti_maginate/general/maginate_lead_sync_on_login';
        
    public function __construct(
        Orderapi $Api,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Grazitti\Maginate\Helper\Data $dataHelper,
        \Grazitti\Maginate\Model\Data $modelData,
        \Magento\Customer\Model\Customer $customerData,
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        CustomerRepositoryInterface $customerRepository
    ) {
		parent::__construct($context);
        $this->_api = $Api;
        $this->scopeConfig = $scopeConfig;
        $this->jsonHelper = $jsonHelper;
        $this->dataHelper = $dataHelper;
        $this->customerData = $customerData;
        $this->modelData = $modelData;
        $this->_objectManager = $objectmanager;
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $this->_leadIntegration=$this->scopeConfig->getValue(self::XML_PATH_LEAD_INTEGRATION, $storeScope);
		
		$this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
    }

  public function execute()
    {
        $customersUpdated = 0;
		$expiry  = $this->dataHelper->checkExpiry();
		if ($this->_leadIntegration && $expiry) {
			$collection = $this->filter->getCollection($this->collectionFactory->create());
			$customerIds = $collection->getAllIds();
            if (!is_array($customerIds)) {
                $this->messageManager->addError(
                    __('Please select item(s)')
                );
            }else{			
				$cusCount = count($customerIds);
				if ($cusCount > 200) {
					$this->messageManager->addError(
						__('Cannot select more than 200 item(s)')
					);
				} else {
					try {
						$mergeData = [];
						foreach ($customerIds as $customerId) {
							$item = clone $this->modelData;
							$customer = $this->customerData->load($customerId);
							$item->setCustomerId($customerId);
							$item->setSyncWithMarketo(1);
							$item->save();
							$data['FirstName'] = $customer->getFirstname();
							$data['LastName'] = $customer->getLastname();
							$data['Email'] = $customer->getEmail();
							$allfield = ["email"];
							array_push($mergeData, $data);
						}
							$status='';
						$name='';
						$count = count($mergeData);
						$this->_api->postmassData($mergeData, $status, $name);
						$this->messageManager->addSuccess(
							__('Customers have been synced successfully with Marketo')
						);
					} catch (\Exception $e) {
						$this->messageManager->addError(
							__($e->getMessage())
						);
					}
				}
			}
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setRefererOrBaseUrl();
        return $resultRedirect;
    }
}
