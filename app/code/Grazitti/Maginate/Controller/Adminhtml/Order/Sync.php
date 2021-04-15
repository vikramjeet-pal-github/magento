<?php
/**
 * Copyright © 2020 Graziiti. All rights reserved.
 */

    namespace Grazitti\Maginate\Controller\Adminhtml\Order;

    use Magento\Framework\Stdlib\DateTime;
    use Grazitti\Maginate\Model\Orderapi;
    use Magento\Framework\App\Config\ScopeConfigInterface;

class Sync extends \Magento\Backend\App\Action
{
    /**
     * Sync Action for Syncing of order with Marketo
     * @return Void
     * */
    protected $_api;
    protected $scopeConfig;
    protected $_leadIntegration;
    const XML_PATH_LEAD_INTEGRATION = 'grazitti_maginate/general/maginate_lead_sync_on_login';
    
    public function __construct(
        Orderapi $Api,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Grazitti\Maginate\Helper\Data $dataHelper,
        \Magento\Customer\Model\Customer $customerData,
        \Grazitti\Maginate\Model\Data $modelData,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_api = $Api;
        $this->scopeConfig = $scopeConfig;
        $this->jsonHelper = $jsonHelper;
        $this->dataHelper = $dataHelper;
        $this->customerData = $customerData;
        $this->modelData = $modelData;
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $this->_leadIntegration=$this->scopeConfig->getValue(self::XML_PATH_LEAD_INTEGRATION, $storeScope);
        parent::__construct($context);
    }
    public function execute()
    {
        $expiry  = $this->dataHelper->checkExpiry();
        if ($this->_leadIntegration && $expiry):
            $customerId = $this->getRequest()->getParam('entity_id');
            $customer = $this->customerData->load($customerId);
            $item = $this->modelData;
            $item->setCustomerId($customerId);
            $item->setSyncWithMarketo(1);
            $item->save();
                $data['FirstName'] = $customer->getFirstname();
                $data['LastName'] = $customer->getLastname();
                $data['Email'] = $customer->getEmail();
                    
            try {
                $lead=$this->_api->getleadData();
                $this->_api->leadIntegration($data);
                $this->messageManager->addSuccess(
                    __('Customer has been synced successfully with Marketo')
                );
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('We can\'t process your request right now. Sorry, that\'s all we know.')
                );
            }
        endif;
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setRefererOrBaseUrl();
        return $resultRedirect;
    }
}
