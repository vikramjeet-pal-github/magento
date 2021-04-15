<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Helper;

use Vonnda\Subscription\Helper\Logger;
use Magento\Framework\App\Helper\Context;

use Carbon\Carbon;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\Template\FilterProvider;

class Data extends AbstractHelper
{
    const XML_PATH_SUBSCRIPTIONS_GENERAL_CONFIG = 'vonnda_subscriptions_general/';
    const XML_PATH_SUBSCRIPTIONS_CRON_CONFIG = 'vonnda_subscriptions_cron/';
    const XML_PATH_SUBSCRIPTIONS_AUTO_REFILL_CANCEL_CONFIG = 'vonnda_subscriptions_cancel_auto_refill/';
    
    const STORE_CODE_US = 'mlk_us_sv';
    const STORE_CODE_CA = 'mlk_us_ca';

    /**
     * Vonnda Logger
     *
     * @var \Magento\Sales\Helper\Logger $logger
     */
    protected $logger;

    /**
     * Search Criteria Builder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     *
     * @var BlockFactory $blockFactory
     */
    protected $storeManager;

    /**
     *
     * @var BlockFactory $blockFactory
     */
    protected $blockFactory;

    /**
     *
     * @var FilterProvider $filterProvider
     */
    protected $filterProvider;

    public function __construct(
        Context $context,
        Logger $logger,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StoreManagerInterface $storeManager,
        BlockFactory $blockFactory,
        FilterProvider $filterProvider
    )
    {
        $this->logger = $logger;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeManager = $storeManager;
        $this->blockFactory = $blockFactory;
        $this->filterProvider = $filterProvider;
        parent::__construct($context);
    }

    //Module Configuration
    public function getConfigValue($field, $storeId = null)
	{
		return $this->scopeConfig->getValue(
			$field, ScopeInterface::SCOPE_STORE, $storeId
		);
    }

    public function getGeneralConfig($code, $storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH_SUBSCRIPTIONS_GENERAL_CONFIG .'general/'. $code, $storeId);
	}

	public function getCronConfig($code, $storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH_SUBSCRIPTIONS_CRON_CONFIG .'cron_config/'. $code, $storeId);
    }

    public function isAutoRefillCancelEnabled($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_SUBSCRIPTIONS_AUTO_REFILL_CANCEL_CONFIG .'config/enabled', $storeId);
    }

    public function getAutoRefillCancelQuestion($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_SUBSCRIPTIONS_AUTO_REFILL_CANCEL_CONFIG .'config/question', $storeId);
    }

    public function getAutoRefillCancelAnswers($storeId = null)
    {
        $answerOptions = explode(",", 
            $this->getConfigValue(self::XML_PATH_SUBSCRIPTIONS_AUTO_REFILL_CANCEL_CONFIG .'config/answer_options', $storeId));
        if($this->shouldRandomizeAutoRefillAnswers($storeId)){
            shuffle($answerOptions);
        }

        return $answerOptions;
    }

    public function shouldRandomizeAutoRefillAnswers($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_SUBSCRIPTIONS_AUTO_REFILL_CANCEL_CONFIG .'config/randomize_answer_options', $storeId);
    }

    public function getAutoRefillShippingOption($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_SUBSCRIPTIONS_CRON_CONFIG .'cron_config/shipping_option', $storeId);
    }

    public function getAutoRefillShippingPriceOverride($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_SUBSCRIPTIONS_CRON_CONFIG .'cron_config/shipping_price_override', $storeId);
    }

    public function getAutoRefillShippingOptionInternational($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_SUBSCRIPTIONS_CRON_CONFIG .'cron_config/shipping_option_international', $storeId);
    }

    public function getAutoRefillShippingPriceOverrideInternational($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_SUBSCRIPTIONS_CRON_CONFIG .'cron_config/shipping_price_override_international', $storeId);
    }

    public function isSubManagerDebugLogEnabled($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_SUBSCRIPTIONS_GENERAL_CONFIG .'general/sub_manager_debug_log', $storeId);
    }

    //Utility Functions
    public function returnFirstItem($collection)
    {
        foreach($collection as $item){
            return $item;
        }
        
        return false;
    }

    public function isZeroNumber($field)
    {
        if($field === false){
            return false;
        }

        if($field === ""){
            return false;
        }

        if($field === null){
            return false;
        }

        $num = floatval($field);
        if($num == 0){
            return true;
        }

        return false;
    }

    //CMS Block Helper
    public function getCMSBlockHtml($blockIdentifier, $templateVariables = [])
    {
        $store = $this->storeManager->getStore();
        $block = $this->blockFactory->create()->setStoreId($store->getId())->load($blockIdentifier);
        if($block->getIsActive()) {
            $filter = $this->filterProvider
                ->getBlockFilter()
                ->setStoreId($store->getId())
                ->setVariables($templateVariables)
                ->filter($block->getContent());
            return $filter;
        }
        return "";
    }

}