<?php
/**
 * Copyright © 2018 Graziiti. All rights reserved.
 */

namespace Grazitti\Maginate\Model;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Grazitti\Maginate\Model\Logs;

/**
 * Fancyfeedbacktab fancyfeedback model
 */
class Orderapi extends \Magento\Framework\Model\AbstractModel
{
   /**
    * @param \Magento\Framework\Model\Context $context
    * @param \Magento\Framework\Registry $registry
    * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
    * @param \Magento\Framework\Data\Collection\Db $resourceCollection
    * @param array $data
    */
    protected $host;
    protected $clientId;
    protected $clientSecret;
    protected $partnerId;
    protected $logs;
    public $_objectManager;
    public $filterType; //field to filter off of, required
    public $filterValues; //one or more values for filter, required
    public $fields;//one or more fields to return
    public $batchSize;
    public $input;
    public $action;
    public $cookie;
    public $nextPageToken;
    public $id;//id of lead to return
    public $scopeConfig;
    public $lookupField;
    public $_serialize;
    public $_curl;
    protected $configWriter;
    const XML_PATH_MUNCHKIN_ID = 'grazitti_maginate/email/munchkin_id';
    const XML_PATH_CLIENT_ID = 'grazitti_maginate/email/client_id';
    const XML_PATH_SECRET_KEY = 'grazitti_maginate/email/secret_key';
    
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Grazitti\Maginate\Model\LogsFactory $logs,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        //\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        //\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Grazitti\Maginate\Helper\Construct $cookiehelper,
        \Magento\Framework\Serialize\SerializerInterface $serialize,
        \Magento\Framework\HTTP\Client\Curl $curl,
        array $data = []
    ) {
		$resourceCollection = null;
		$resource = null;
        $this->_objectManager = $objectmanager;
        $this->scopeConfig = $scopeConfig;
        $this->logs = $logs;
        $this->_curl = $curl;
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $this->host= 'https://'.$this->scopeConfig->getValue(self::XML_PATH_MUNCHKIN_ID, $storeScope).'.mktorest.com';
        $this->partnerId='b6a48d82a37692c8ac6efbd6185963b43fefc1ab_e8d4c5097ad946a2ef9b122aa9135b943da25415';
        $this->clientId=$this->scopeConfig->getValue(self::XML_PATH_CLIENT_ID, $storeScope);
        $this->clientSecret=$this->scopeConfig->getValue(self::XML_PATH_SECRET_KEY, $storeScope);
        $this->configWriter   = $configWriter;
        $this->cookiehelper = $cookiehelper;
        $this->_serialize = $serialize;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }
    private function writeResponseLog($exception)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/magentoMarketoConnector.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info($exception);
    }
    
    private function getPostCurlData($url, $data, $mass = null)
    {
        if ($mass == 1) {
            $requestBody = $this->bodymassBuilder($data);
        } else {
            $requestBody = $this->bodyBuilder($data);
        }
        $headers = ['accept: application/json','Content-Type: application/json' ];
        if ($data) {
            $this->_curl->setOption(CURLOPT_HTTPHEADER, $headers);
            $this->_curl->setOption(CURLOPT_RETURNTRANSFER, 1);
            $this->_curl->setOption(CURLOPT_SSL_VERIFYHOST, 0);
            $this->_curl->setOption(CURLOPT_SSL_VERIFYPEER, 0);
            $this->_curl->post($url, $requestBody);
            
        } else {
            $this->_curl->setOption(CURLOPT_HTTPHEADER, $headers);
            //    WARNING: this would prevent curl from detecting a 'man in the middle' attack
            $this->_curl->setOption(CURLOPT_SSL_VERIFYHOST, 0);
            $this->_curl->setOption(CURLOPT_SSL_VERIFYPEER, 0);
            $this->_curl->get($url);
            
        }
        
        $response = $this->_curl->getBody();
        if ($response) {
            if (is_array($response)) {
                $responseFinal = json_encode($response);
            } else {
                $responseFinal = $response;
            }
            return $responseFinal;
        } else {
            return "Parameters not valid";
        }
    }
    public function leadIntegration($data)
    {
        $url = $this->host . "/rest/v1/leads.json?access_token=" . $this->getToken();
        $this->action = 'createOrUpdate';
        $this->lookupField = 'email';
        $mass = 0;
        $response = $this->getPostCurlData($url, $data, $mass);
        $responseDecode = json_decode(($response), true);
            
        if (isset($responseDecode['success'])) {
            if ($this->cookiehelper->getCookie('_mkto_trk')) {
            
                $this->cookie =  urlencode("_mkto_trk=".$this->cookiehelper->getCookie('_mkto_trk'));
                $this->id = $responseDecode['result'][0]['id'];
                $this->mergenAonymousLead();
            }
        }
        /* Insert Logs */
        $model = $this->logs->create();
        $model->addData([
            "api_params" => $this->_serialize->serialize($data),
            "message" => $responseDecode['result'][0]['status'],
            "api_url" => $url,
            "success" => $responseDecode['success'],
            "response" => $response
        ]);
        $saveLogs = $model->save();
        return $response;
    }
    public function postmassData($data, $status, $cname)
    {
        if ($status==1) {
            $url = $this->host . "/rest/v1/customobjects/" . $cname . ".json?access_token=" . $this->getToken();
        } else {
            $url = $this->host . "/rest/v1/leads.json?access_token=" . $this->getToken();
        }
        $this->action = 'createOrUpdate';
        $mass = 1;
        $response = $this->getPostCurlData($url, $data, $mass);
        if ($status!=1) {
            $responseDecode = json_decode(($response), true);
            $getCookie = $this->cookiehelper->getCookie('_mkto_trk');
            if (isset($responseDecode['success']) && isset($getCookie)) {
                   $this->idCookieLead = $responseDecode['result'][0]['id'];
                   $this->cookie =  urlencode("_mkto_trk=".$getCookie);
                   $this->mergenAonymousLead($data);
            }
        }
        return $response;
    }
    public function leadUpdate($data)
    {
        $url = $this->host . "/rest/v1/leads.json?access_token=" . $this->getToken();
        $this->action = 'createOrUpdate';
        $this->lookupField = 'id';
        $response = $this->getPostCurlData($url, $data);
        
        $responseDecode = json_decode(($response), true);
        if (isset($responseDecode['success'])) {
            if ($this->cookiehelper->getCookie('_mkto_trk')) {
                $this->id = $responseDecode['result'][0]['id'];
                $this->cookie =  urlencode("_mkto_trk=".$this->cookiehelper->getCookie('_mkto_trk'));
                $this->mergenAonymousLead();
            }
        }
        /* Insert Logs */
        $model = $this->logs->create();
        $model->addData([
            "api_params" => $this->_serialize->serialize($data),
            "api_url" => $url,
            "success" => $responseDecode['success'],
            "message" => $responseDecode['result'][0]['status'],
            "response" => $response
        ]);
        $saveLogs = $model->save();
        return $response;
    }
    public function mergenAonymousLead()
    {
        $url = $this->host . "/rest/v1/leads/" . $this->id;
        $url .= "/associate.json?access_token=" . $this->getToken();
        $url .= "&cookie=" . $this->cookie;
        
        $data = true;
        $mass = 0;
        $response = $response = $this->getPostCurlData($url, $data, $mass);
        return $response;
    }
    public function getleadData($email = null)
    {
        if ($email) {
                        
            $this->fields = ["email"];
            $this->filterType="EMAIL";
            $this->filterValues = [$email];
            
            $url = $this->host . "/rest/v1/leads.json?access_token=" . $this->getToken();
            $url .= "&filterType=" . $this->filterType;
            $url .= "&filterValues=" .$this->csvString($this->filterValues);
                        
            if (isset($this->fields)) {
                $url = $url . "&fields=" . $this->csvString($this->fields);
            }
            $data = false;
            $mass = 0;
            $response = $this->getPostCurlData($url, $data, $mass);
                    
            return $response;
        } elseif ($this->cookiehelper->getCookie('_mkto_trk')) {
            $this->fields =["email"];
            $this->filterType="COOKIE";
            $cookieValue=urlencode("_mkto_trk=".$this->cookiehelper->getCookie('_mkto_trk'));
             $this->filterValues = [$cookieValue];
            
            $url = $this->host . "/rest/v1/leads.json?access_token=" . $this->getToken();
            $url .= "&filterType=" . $this->filterType;
            $url .= "&filterValues=" .$this->csvString($this->filterValues);
            if (isset($this->fields)) {
                $url = $url . "&fields=" . $this->csvString($this->fields);
            }
            $data = false;
            $mass = 0;
            $response = $this->getPostCurlData($url, $data, $mass);
            return $response;
        } else {
            return false;
        }
    }
    public function getleadDataForm($ctype, $cvalue, $allfield)
    {
        $this->fields = $allfield;
        $this->filterType=$ctype;
        $this->filterValues=[urlencode($cvalue)];
        $url = $this->host . "/rest/v1/leads.json?access_token=" . $this->getToken();
        $url .= "&filterType=" . $this->filterType;
        $url .= "&filterValues=" .$this->csvString($this->filterValues);
        if (isset($this->batchSize)) {
            $url = $url . "&batchSize=" . $this->batchSize;
        }
        if (isset($this->nextPageToken)) {
            $url = $url . "&nextPageToken=" . $this->nextPageToken;
        }
        if (isset($this->fields)) {
            $url = $url . "&fields=" . $this->csvString($this->fields);
        }
        return $this->getCurlData($url);
    }

    public function orderData($data, $status, $name)
    {
        if ($status==1) {
            $url = $this->host . "/rest/v1/customobjects/" . $name;
            $url .= ".json?access_token=" . $this->getToken();
        } else {
            $url = $this->host . "/rest/v1/leads.json?access_token=" . $this->getToken();
        }
        $this->action = 'createOrUpdate';
        $this->lookupField = 'email';
        $mass = 0;
        $response = $this->getPostCurlData($url, $data, $mass);
        $responseDecode = json_decode(($response), true);
        /* Insert Logs */
        $model = $this->logs->create();
        $model->addData([
            "api_params" => $this->_serialize->serialize($data),
            "api_url" => $url,
            "success" => $responseDecode['success'],
            "message" => $responseDecode['result'][0]['status'],
            "response" => $response
        ]);
        $saveLogs = $model->save();
        
        return $response;
    }
    public function abandoncartData($data, $status, $name)
    {
        if ($status==1) {
            $url = $this->host . "/rest/v1/customobjects/" . $name;
            $url .=    ".json?access_token=" . $this->getToken();
        } else {
            $url = $this->host . "/rest/v1/leads.json?access_token=" . $this->getToken();
        }
        $this->action = 'createOrUpdate';
        $this->lookupField = 'email';
        $mass = 0;
        $response = $this->getPostCurlData($url, $data, $mass);
        return $response;
    }
    
    public function expiryResponse($key, $domain)
    {
        
        $url = "https://api.grazitti.com/marketo/secure/access/restApi.php?licencekey=";
        $url .= $key . "&domain=" . $domain . "&action=validate";
        
        $this->_curl->setOption(CURLOPT_RETURNTRANSFER, 1);
        $this->_curl->setOption(CURLOPT_SSL_VERIFYHOST, 0);
        $this->_curl->setOption(CURLOPT_SSL_VERIFYPEER, 0);
        $this->_curl->setOption(CURLOPT_FOLLOWLOCATION, true);
        $this->_curl->get($url);
        $response = $this->_curl->getBody();
        return $response;
    }
    private function bodyBuilder($data)
    {
        $this-> input = [$data];
        $body = new  \stdClass;
        
        if (isset($this->action)) {
            $body->action = $this->action;
        }
        if (isset($this->lookupField)) {
            $body->lookupField = $this->lookupField;
        }
        $body->input =$this->input;
        $json = json_encode($body);
        return $json;
    }
    private function bodymassBuilder($data)
    {
        $this-> input = $data;
            $body = new \stdClass();
        if (isset($this->action)) {
            $body->action = $this->action;
        }
        if (isset($this->lookupField)) {
            $body->lookupField = $this->lookupField;
        }
            $body->input =$this->input;
            $json = json_encode($body);
            return $json;
    }
    public function getLeadById()
    {
        $url = $this->host . "/rest/v1/lead/" . $this->id . ".json?access_token=" . $this->getToken();
        $data = false;
        $mass = 0;
        $response = $this->getPostCurlData($url, $data, $mass);
        return $response;
    }
    public function getCurlData($url)
    {
        $data = false;
        $mass= 0;
        $response = $this->getPostCurlData($url, $data, $mass);
        return $response;
    }
    public function getToken()
    {
        $cookie_name = '_maginate_two_access_token_cookie';
        // check access token exists, not expired
        $getCookie = $this->cookiehelper->getCookie($cookie_name);
        if (!isset($getCookie)) {
            $url = $this->host . "/identity/oauth/token?grant_type=client_credentials&client_id=";
            $url .= $this->clientId . "&client_secret=" . $this->clientSecret;
            $url .= "&partner_id=" . $this->partnerId;
            $data = false;
            $mass = 0;
            $response = $this->getPostCurlData($url, $data, $mass);
            $responseDecode = json_decode(($response));
            if (isset($responseDecode->access_token)) {
                $this->cookiehelper->cookieSet(
                    $cookie_name,
                    $responseDecode->access_token,
                    $responseDecode->expires_in
                );
                $token = $responseDecode->access_token;
            } else {
                return false;
            }
        } else {
            $token = $this->cookiehelper->getCookie($cookie_name);
        }
        return $token;
    }
    public function checkToken($endpoint, $clientid, $secretkey)
    {
        $url = 'https://'.$endpoint.'.mktorest.com';
        $url .= "/identity/oauth/token?grant_type=client_credentials&client_id=";
        $url .= $clientid . "&client_secret=" . $secretkey;
        $data = false;
        $mass= 0 ;
        $response = $this->getPostCurlData($url, $data, $mass);
        $responseDecode = json_decode(($response));
        if ($responseDecode) {
            if (isset($responseDecode->error)) {
                $token='';
            }
            if (isset($responseDecode->access_token)) {
                $token = $responseDecode->access_token;
            }
        } else {
                $token ='';
        }
            
        return $token;
    }
    public function checkObject($name)
    {
        $this->filterType='marketoGUID';
        $this->filterValues = [$name];
        $url = $this->host . "/rest/v1/customobjects/";
        $url .= $name . ".json?access_token=" . $this->getToken();
        $url .= "&filterType=" . $this->filterType;
        $url .= "&filterValues=" .$this->csvString($this->filterValues);
        
        $this->names = [$name];
        if (isset($this->names)) {
            $url .= "&names=" . $this->csvString($this->names);
        }
        $mass = 0;
        $data =false;
        
        $response = $this->getPostCurlData($url, $data, $mass);
        $responseDecode = json_decode($response);
        if ($responseDecode) {
            if (isset($responseDecode->error)) {
                $result='';
            }
            if (isset($responseDecode->success)) {
                $result = $responseDecode->success;
            }
        } else {
                $result ='';
        }
            
        return $result;
    }
    private function csvString($fields)
    {
        $csvString = "";
        $i = 0;
        foreach ($fields as $field) {
            if ($i > 0) {
                $csvString = $csvString . "," . $field;
            } elseif ($i === 0) {
                $csvString = $field;
            }
            $i++;
        }
        return $csvString;
    }
}
