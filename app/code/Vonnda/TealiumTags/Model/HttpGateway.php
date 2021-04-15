<?php

namespace Vonnda\TealiumTags\Model;

use Vonnda\TealiumTags\Logger\DebugLogger;

use Tealium\Tags\Helper\Data as TealiumHelper;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Vonnda\Subscription\Logger\DebugLogger as VonndaDebugLogger;

class HttpGateway
{
    const TEALIUM_COLLECT_URL = "https://collect.tealiumiq.com/event";

    protected $tealiumHelper;

    protected $curl;

    protected $logger;

    /**
     * Store Manager Interface
     *
     * @var \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * Store Repository Interface
     *
     * @var \Magento\Store\Model\StoreRepositoryInterface $storeRepository
     */
    protected $storeRepository;

    public function __construct(
        TealiumHelper $tealiumHelper,
        Curl $curl,
        DebugLogger $logger,
        StoreManagerInterface $storeManager,
        StoreRepositoryInterface $storeRepository
    ) {
        $this->tealiumHelper = $tealiumHelper;
        $this->curl = $curl;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->storeRepository = $storeRepository;
    }

    public function pushTag($utagData)
    {
        try {
            $postData = [];
            $store = $this->storeRepository->get('mlk_us_sv');
            $tealiumAccount = $this->tealiumHelper->getAccount($store);
            $tealiumProfile = $this->tealiumHelper->getProfile($store);

            $tealiumAccountValid = $tealiumAccount && $tealiumProfile;

            if($tealiumAccountValid){
                $postData['tealium_account'] = $tealiumAccount;
                $postData['tealium_profile'] = $tealiumProfile;

                $postData = array_merge($postData, $utagData);
                
                $this->curl->addHeader("Content-Type", "application/json");
                $this->curl->addHeader("Expect", "");
                $this->curl->post(self::TEALIUM_COLLECT_URL, json_encode($postData));
                
                $this->logger->info("Tealium event: " . (isset($postData['tealium_event']) ? $postData['tealium_event'] : "Event field not set"));
                $this->logger->info("Tealium post data: " . json_encode($postData));
                $this->logger->info("Tealium post response status: " . $this->curl->getStatus());
                
                //Body is always empty
                $requestWasSuccessful = $this->curl->getStatus() === 100 ||
                                        $this->curl->getStatus() === 200;
                return $requestWasSuccessful;
            }
        } catch(\Error $e){
            $this->logger->critical($e->getMessage());
        } catch(\Exception $e){
            $this->logger->info($e->getMessage());
        }
    }


}
