<?php

namespace Narvar\Accord\Helper;

use Narvar\Accord\Helper\CustomLogger;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Narvar\Accord\Helper\Sender;
use Narvar\Accord\Helper\Util;
use Narvar\Accord\Config\Config;
use Narvar\Accord\Config\MagentoConfig as MagentoConfig;
use Narvar\Accord\Helper\Constants\Constants;
use Narvar\Accord\Helper\NoFlakeLogger;
use Narvar\Accord\Helper\AccordException;

class Processor
{

    private $logger;

    private $sender;

    private $config;

    private $magentoConfig;

    private $util;

    private $constants;

    private $noFlakeLogger;

    /**
     * Constructor
     *

     * @param JsonHelper   $jsonHelper    JsonHelper for json encoding
     * @param CustomLogger $logger        Custom logger
     * @param Sender       $sender        to send data to narvar
     * @param Config       $config        narvar extension config
     * @param Config       $magentoConfig narvar extension config
     * @param Util         $util          Narvar\Accord\Helper\Util;
     * @param Constants    $constants     Narvar\Accord\Helper\Constants\Constants;
     * @param NoFlakeLogger $noFlakeLogger Narvar\Accord\Helper\NoFlakeLogger;
     */
    public function __construct(
        JsonHelper $jsonHelper,
        CustomLogger $logger,
        Sender $sender,
        Config $config,
        MagentoConfig $magentoConfig,
        Util $util,
        Constants $constants,
        NoFlakeLogger $noFlakeLogger
    ) {
        $this->logger        = $logger;
        $this->jsonHelper    = $jsonHelper;
        $this->sender        = $sender;
        $this->util          = $util;
        $this->magentoConfig = $magentoConfig;
        $this->config        = $config;
        $this->constants     = $constants->getConstants();
        $this->noFlakeLogger = $noFlakeLogger;
    }

    /**
     * Method to check presence of data
     *
     * @param $shipmentData shipment data to be send to Narvar
     * @param $storeId      storeId
     *
     * @return object
     */
    public function isDataPresent($data, $retailerMoniker, $eventName)
    {
        if (!is_array($data) || array_values($data) == $data || !$retailerMoniker || !$eventName) {
            $errorMessage = $this->constants['INVALIDARGUMENTEXCEPTION'] . ' in Processor '
            . __METHOD__;
            $errorLog = [];
            $errorLog['data'] = $data;
            $errorLog['eventName'] = $eventName;
            $errorLog['message'] = $errorMessage;
            $this->logger->error(json_encode($errorLog));
            throw new AccordException($errorMessage);
        }
    }

    /**
     * Method to check presence of data
     *
     * @param $shipmentData shipment data to be send to Narvar
     * @param $storeId      storeId
     *
     * @return object
     */
    public function isAuthDataPresent($authObject, $narvarAuth, $narvarRetailerMoniker)
    {
        if (
            !is_array($authObject) || array_values($authObject) == $authObject
            || !$narvarAuth || !$narvarRetailerMoniker
        ) {
            $errorMessage = $this->constants['INVALIDARGUMENTEXCEPTION'] . ' in Processor '
            . __METHOD__;
            $errorLog = [];
            $errorLog['data'] = $authObject;
            $errorLog['retailer'] = $narvarRetailerMoniker;
            $errorLog['message'] = $errorMessage;
            $this->logger->error(json_encode($errorLog));
            throw new AccordException($errorMessage);
        }
    }


    /**
     * Method to push data to narvar
     *
     * @param $data    data to be send to Narvar
     * @param $storeId storeId
     * @param $apiKey  destination rest api key
     *
     * @return object
     */
    public function sendPluginData($data, $retailerMoniker, $eventName)
    {
        $this->isDataPresent($data, $retailerMoniker, $eventName);
        $configData = $this->config->getConfigByStoreId($data['store_id']);
        $api      = $configData[$this->constants['DATA_HOST']] . $configData[$eventName];
        $placeholderKey = '{orderNumber}';
        $api      = str_replace($placeholderKey, $data['increment_id'], $api);
        $header   = $this->addNarvarHeader($data, $retailerMoniker, $eventName);
        switch ($eventName) {
            case "narvar_order_plugin":
                return $this->sender->post($api, $data, $header);
            case "narvar_invoice_plugin":
                return $this->sender->put($api, $data, $header);
            case "narvar_shipment_plugin":
                return $this->sender->put($api, $data, $header);
            default:
                $errorMessage = 'Invalid Event in Processor'
                . __METHOD__;
                $errorLog = [];
                $errorLog['data'] = $data;
                $errorLog['retailer'] = $retailerMoniker;
                $errorLog['message'] = $errorMessage;
                $this->logger->error(json_encode($errorLog), $data['store_id']);
                throw new AccordException($errorMessage);
        }
    }
    /**
     * Method to push auth data to narvar
     *
     * @param $authData Auth data to be send to Narvar
     *
     * @return object
     */
    public function sendAuthData($authObject, $narvarAuth, $narvarRetailerMoniker, $isProduction)
    {
        $this->isAuthDataPresent($authObject, $narvarAuth, $narvarRetailerMoniker);
        $headers  = $this->addNarvarAuthHeader($authObject, $narvarAuth, $narvarRetailerMoniker);
        $configData = $this->config->getConfigByIsProduction($isProduction);
        $api      = $configData[$this->constants['AUTH_HOST']] . $configData[$this->constants['AUTH_API']];
        return  $this->sender->post($api, $authObject, $headers, true);
    }


    /**
     * Method to create hmac header and retailer moniker header.
     *
     * @param $data      String data
     * @param $storeId   storeId
     * @param $eventName eventName
     *
     * @return object
     */
    public function addNarvarHeader($data, $retailerMoniker, $eventName)
    {
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $authKey = $this->util->getAuthKey($data['store_id']);
        $hmacKey  = $this->util->createHmacKey($authKey, $jsonData);
        $headers[$this->constants['RETAILER_MONIKER_HEADER']] = $retailerMoniker;
        $headers[$this->constants['HMAC_HEADER']]  = $hmacKey;
        $headers[$this->constants['EVENT_HEADER']] = $eventName;
        $headers[$this->constants['STORE_HEADER']] = $data['store_id'];
        return $headers;
    }


    public function addNarvarAuthHeader($authObject, $narvarAuth, $narvarRetailerMoniker)
    {
        $jsonData = json_encode($authObject, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $hmacKey = $this->util->createHmacKey($narvarAuth, $jsonData);
        $headers[$this->constants['RETAILER_MONIKER_HEADER']] = $narvarRetailerMoniker;
        $headers[$this->constants['HMAC_HEADER']]  = $hmacKey;
        return $headers;
    }
}
