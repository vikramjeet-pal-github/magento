<?php
/**
 * Copyright © 2020 Grazitti. All rights reserved.
 */
namespace Grazitti\Maginate\Controller\Index;

class Data extends \Magento\Framework\App\Action\Action
{
    protected $resultPageFactory;
    protected $resultJsonFactory;
    public $scopeConfig;
    protected $customerSession;
    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Grazitti\Maginate\Model\Orderapi $api,
        \Grazitti\Maginate\Helper\Construct $cookiehelper,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->api = $api;
        $this->cookiehelper = $cookiehelper;
        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
         /**************************DDoS attack*******************************/
         $enable_dos = $this->scopeConfig->getValue('grazitti_maginate/dos_prevention/enable_dos');
         $enable_prefill = $this->scopeConfig->getValue('grazitti_maginate/autofill/maginate_autofill_configuration');
         
        if ($enable_dos==1) {
            $stop_dos = 0;
            $allowedHits = $this->scopeConfig->getValue('grazitti_maginate/dos_prevention/allowed_hits');
            --$allowedHits;
            $timeUnit = $this->scopeConfig->getValue('grazitti_maginate/dos_prevention/time_unit');
               $time = $this->scopeConfig->getValue('grazitti_maginate/dos_prevention/time');
               $ban_time = $this->scopeConfig->getValue('grazitti_maginate/dos_prevention/ban_time');
               $unit_ban_time = $this->scopeConfig->getValue('grazitti_maginate/dos_prevention/ban_time_unit');
               $autofill = $this->scopeConfig->getValue('grazitti_maginate/autofill/maginate_autofill_configuration');
               $allowedTime = "";
               $banElapseTime = "";
            if ($time == "min") {
                $allowedTime = $timeUnit*60;
            } else {
                $allowedTime = $timeUnit;
            }
                   $banElapseTime = $unit_ban_time*60*60;
            if (!$this->customerSession->getHitCount()) { // Visitor first access
                $this->customerSession->setHitCount(1);
                $this->customerSession->setFirstHitTime(time());
            } else {
                if ($this->customerSession->getHitCount() >= $allowedHits) {
                      $requestTimeInterval =  time() - $this->customerSession->getFirstHitTime();
                    if ($requestTimeInterval < $allowedTime) { //Too Frequent hits
                        if ($autofill == 1) {
                            $this->customerSession->setBanVisitor(true);
                        }
                        $this->customerSession->setBanVisitorTime(time());
                        $stop_dos = 1;
                    }
                }
                     $this->customerSession->setHitCount($this->customerSession->getHitCount()+1);
            }
        }
        /**************************DDoS attack*******************************/
        $field = $this->getRequest()->getParam('fieldname');
        
        if (!is_array($field)) {
            $field = [];
        }
        unset($field["formid"]);
        unset($field["munchkinId"]);
        $allfield = array_keys($field);
        $getCookie = $this->cookiehelper->getCookie('_mkto_trk');
        $result = $this->resultJsonFactory->create();
        if (!$enable_prefill) {
                $response = '{"success" : false , "message" : "Prefill functionality disabled!"}';
                return $result->setData($response);
        }
        $token = '';
        $response = '{"success" : false , "message" : "Something went wrong!"}';
        if (isset($getCookie)) {  //Check _mkto_trk cookie
            $lead = $this->api->getleadDataForm('COOKIE', $getCookie, $allfield);
            $token= $this->api->getToken();
        } else {  //_mkto_trk is not set.
            $response = '{"success" : false , "message" : "_mkto_trk not found."}';
            return $result->setData($response);
        }
        if (!$token) {
            $response = '{"success" : false , "message" : "REST API access not found."}';
            return $result->setData($response);
        } else {
            $response = $lead;
        }
        return $result->setData($response);
    }
}
