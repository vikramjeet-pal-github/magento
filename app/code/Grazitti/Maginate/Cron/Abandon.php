<?php
/**
 * Copyright © 2020 Graziiti. All rights reserved.
 */
namespace Grazitti\Maginate\Cron;

use \Psr\Log\LoggerInterface;
use Grazitti\Maginate\Model\Orderapi;

class Abandon extends \Magento\Framework\App\Action\Action
{

    protected $scopeConfig;
    protected $_api;
    protected $quoteItemCollectionFactory;
    protected $_quotesFactory;
    protected $_storeManager;
    protected $queryResolver;
    protected $_productRepository;
    protected $_urlInterface;
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\QueryResolver $queryResolver,
        \Magento\Reports\Model\ResourceModel\Quote\Item\CollectionFactory $quoteItemCollectionFactory,
        \Magento\Reports\Model\ResourceModel\Quote\CollectionFactory $quotesFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $urlInterface,
        Orderapi $Api
    ) {
        $this->_api = $Api;
        $this->scopeConfig = $scopeConfig;
        $this->quoteItemCollectionFactory = $quoteItemCollectionFactory;
        $this->queryResolver = $queryResolver;
        $this->_quotesFactory = $quotesFactory;
        $this->_storeManager = $storeManager;
        $this->_productRepository = $productRepository;
        $this->_urlInterface = $urlInterface;
    
        parent::__construct($context);
    }

    public function execute()
    {
        $sStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $gKey = 'grazitti_abandon/abandon_cart/';
        $enable=$this->scopeConfig->getValue($gKey.'abandon_enable', $sStore);
        $abandonobjectname = $this->scopeConfig->getValue($gKey.'abandon_cart_custom_object_api_name', $sStore);
        $media_path = $this->scopeConfig->getValue($gKey.'abandon_cart_media_path', $sStore);
        // Get all Active abandon Carts
        if ($enable) {
            $abandonCartCollection = $this->_quotesFactory->create();
            $abandonCartCollection->prepareForAbandonedReport($this->_storeManager->getStore()->getId());
            foreach ($abandonCartCollection as $abandonCart) {
                $abandondata = [];
                $productNames = $abandonCartId = $imageUrls = '';
                $status ='';
                $productName=[];
                $productImage=[];
                $abandonCartId = $abandonCart->getEntity_id();
                $abandondata['quoteid'] = $abandonCartId;
                $abandondata['emailAddress'] = $abandonCart->getCustomer_email();
                $itemCollection = $this->quoteItemCollectionFactory->create();
                $itemCollection->prepareActiveCartItems();
                $itemCollection->addFieldToFilter('quote_id', ['eq'=>$abandonCartId]);
                if (count($itemCollection)) {
                    foreach ($itemCollection as $item) {
                         $productName[] = $item->getName();
                         $product  = $this->_productRepository->getById($item->getProductId());
                         $store = $this->_storeManager->getStore();
                         $mType = \Magento\Framework\UrlInterface::URL_TYPE_MEDIA;
                         $imageUrl = $store->getBaseUrl($mType) . 'catalog/product' . $product->getImage();
                        if ($media_path) {
                            $img_string = explode($media_path, $imageUrl);
                            $image_result = $media_path.$img_string[1];
                        } else {
                            $img_string = explode('/pub/media/', $imageUrl);
                            $image_result = '/pub/media/'.$img_string[1];
                        }
                        $productImage[] = $image_result;
                        $pathUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
                    }
                    if (is_array($productName)) {
                        $productNames .= implode(';', $productName);
                    }
                    if (is_array($productImage)) {
                        $imageUrls .= implode(';', $productImage);
                    }
                    $url = $this->_urlInterface->getUrl('checkout');
                    $abandondata['productNames'] = $productNames;
                    $abandondata['imageUrls'] = $imageUrls;
                    $abandondata['cartLink'] =$url;
                    $abandondata['websitePath'] =$pathUrl;
                    if (!empty($productNames)) {
                        $abandondata['status'] ="abandoned" ;
                    } else {
                        $abandondata['status'] ="deleted" ;
                    }
                }
                $aname = $abandonobjectname;
                $status = 1;
                $response= $this->_api->abandoncartData($abandondata, $status, $aname);
            }
        }
        return $this;
    }
}
