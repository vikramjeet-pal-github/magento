<?php

namespace Vonnda\AlternativeOrigin\Model;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Xml\Security;
use Magento\Framework\Webapi\Soap\ClientFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\CacheInterface;

class Carrier extends \Magento\Fedex\Model\Carrier
{
    const DEBUG = false;

    const CACHE_TAG = 'shipping-rate-request';

    const CACHE_PREFIX = 'shipping-rate-request-';

    const CACHE_LIFETIME = null;
    
    protected $serializer;

    protected $cache;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param Security $xmlSecurity
     * @param \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory
     * @param \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory
     * @param \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param \Magento\Directory\Helper\Data $directoryData
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Module\Dir\Reader $configReader
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param array $data
     * @param Json|null $serializer
     * @param ClientFactory|null $soapClientFactory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        Security $xmlSecurity,
        \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory,
        \Magento\Shipping\Model\Rate\ResultFactory $rateFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
        \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory,
        \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Directory\Helper\Data $directoryData,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Module\Dir\Reader $configReader,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        CacheInterface $cache,
        array $data = [],
        Json $serializer = null,
        ClientFactory $soapClientFactory = null
    ) {
        parent::__construct(
            $scopeConfig,
            $rateErrorFactory,
            $logger,
            $xmlSecurity,
            $xmlElFactory,
            $rateFactory,
            $rateMethodFactory,
            $trackFactory,
            $trackErrorFactory,
            $trackStatusFactory,
            $regionFactory,
            $countryFactory,
            $currencyFactory,
            $directoryData,
            $stockRegistry,
            $storeManager,
            $configReader,
            $productCollectionFactory,
            $data,
            $serializer,
            $soapClientFactory
        );

        $this->cache = $cache;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Json::class);
    }

    /**
     * Makes remote request to the carrier and returns a response
     *
     * @param string $purpose
     * @return mixed
     */
    protected function _doRatesRequest($purpose)
    {
        $this->logDebug("Shipping rate request.");
        if(self::DEBUG){
            $startTime = microtime(true);
        }
        $ratesRequest = $this->_formRateRequest($purpose);
        $ratesRequestNoShipTimestamp = $ratesRequest;
        unset($ratesRequestNoShipTimestamp['RequestedShipment']['ShipTimestamp']);
        $requestString = $this->serializer->serialize($ratesRequestNoShipTimestamp);
        $response = $this->_getCachedQuotes($requestString);
        $debugData = ['request' => $this->filterDebugData($ratesRequest)];
        if ($response === null) {
            $this->logDebug("Fetching shipping rate.");
            try {
                $client = $this->_createRateSoapClient();
                $response = $client->getRates($ratesRequest);
                $this->_setCachedQuotes($requestString, $response);
                $debugData['result'] = $response;
            } catch (\Exception $e) {
                $debugData['result'] = ['error' => $e->getMessage(), 'code' => $e->getCode()];
                $this->_logger->critical($e);
            }
        } else {
            $this->logDebug("Using cached value.");
            $debugData['result'] = $response;
        }
        $this->_debug($debugData);

        if(self::DEBUG){
            $endTime = microtime(true);
            $this->logDebug($endTime - $startTime);
        }
        return $response;
    }

    /**
     * KEPT AS REFERENCE, NOT CURRENTLY USED
     * Makes remote request to the carrier and returns a response
     *
     * @param string $purpose
     * @return mixed
     */
    protected function _doRatesRequestNoCache($purpose)
    {
        $this->logDebug("Shipping rate request - no cache.");
        if(self::DEBUG){
            $startTime = microtime(true);
        }
        $ratesRequest = $this->_formRateRequest($purpose);
        $ratesRequestNoShipTimestamp = $ratesRequest;
        unset($ratesRequestNoShipTimestamp['RequestedShipment']['ShipTimestamp']);
        $response = null; // fixing a notice about $response not being set
        $debugData = ['request' => $this->filterDebugData($ratesRequest)];

        try {
            $client = $this->_createRateSoapClient();
            $response = $client->getRates($ratesRequest);
            $debugData['result'] = $response;
        } catch (\Exception $e) {
            $debugData['result'] = ['error' => $e->getMessage(), 'code' => $e->getCode()];
            $this->_logger->critical($e);
        }
        $this->_debug($debugData);
        if(self::DEBUG){
            $endTime = microtime(true);
            $this->logDebug($endTime - $startTime);
        }
        return $response;
    }

    /**
     * Checks whether some request to rates have already been done, so we have cache for it
     *
     * Used to reduce number of same requests done to carrier service during one session
     * Returns cached response or null
     *
     * @param string|array $requestParams
     * @return null|string
     */
    protected function _getCachedQuotes($requestParams)
    {

        $key = $this->_getQuotesCacheKey($requestParams);
        $this->logDebug('Getting cache key: ' . self::CACHE_PREFIX . $key);

        if(isset(self::$_quotesCache[$key])){
            $this->logDebug('Using response cached on object.');
            return self::$_quotesCache[$key];
        };

        $response = $this->cache->load(self::CACHE_PREFIX  . $key);
        if($response){
            $this->logDebug('Using response cached in global cache.');
            $response = unserialize($response);
            self::$_quotesCache[$key] = $response;
            return $response;
        }

        $this->logDebug('No response value cached.');
        return null;
    }

    /**
     * Sets received carrier quotes to cache
     *
     * @param string|array $requestParams
     * @param string $response
     * @return $this
     */
    protected function _setCachedQuotes($requestParams, $response)
    {
        $key = $this->_getQuotesCacheKey($requestParams);
        $this->logDebug('Setting response in caches ' . self::CACHE_PREFIX  . $key);
        self::$_quotesCache[$key] = $response;
        $this->cache->save(serialize($response), self::CACHE_PREFIX  . $key, [self::CACHE_TAG], self::CACHE_LIFETIME);

        return $this;
    }

    /**
     * Returns cache key for some request to carrier quotes service
     *
     * @param string|array $requestParams
     * @return string
     */
    protected function _getQuotesCacheKey($requestParams)
    {
        if (is_array($requestParams)) {
            $requestParams = implode(
                ',',
                array_merge([$this->getCarrierCode()], array_keys($requestParams), $requestParams)
            );
        }

        return crc32($requestParams);
    }

    /**
     * Conditional logger
     *
     * @param string
     * @return void
     */
    protected function logDebug($message)
    {
        if(self::DEBUG){
            $this->_logger->info($message);
        }
    }
}
