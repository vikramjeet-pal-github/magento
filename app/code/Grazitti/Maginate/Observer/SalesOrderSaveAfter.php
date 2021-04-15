<?php
/**
 * Copyright © 2020 Grazitti. All rights reserved.
 */
namespace Grazitti\Maginate\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Grazitti\Maginate\Model\Orderapi;

class SalesOrderSaveAfter implements ObserverInterface
{
    protected $scopeConfig;
    protected $_catalogProductTypeConfigurable;
    protected $dataHelper;
    protected $productRepository;
    protected $product;
    protected $_countryFactory;
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Orderapi $Api,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable,
        \Grazitti\Maginate\Helper\Construct $dataHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\ProductFactory $product,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Grazitti\Maginate\Model\Data $modelData
    ) {
         $this->scopeConfig = $scopeConfig;
        $this->_api = $Api;
        $this->_catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
        $this->dataHelper = $dataHelper;
        $this->product = $product;
        $this->productRepository = $productRepository;
        $this->_countryFactory = $countryFactory;
        $this->modelData = $modelData;
    }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $enable=$this->scopeConfig->getValue('grazitti_maginate/orderconfig/maginate_order_integration', $scope);
        if (!$enable) {
            return $this;
        }
        $order = $observer->getEvent()->getOrder();
        $status = 0;
        $name='';
        $data['Email'] = $order->getCustomerEmail();
        $data['FirstName'] = $order->getCustomerFirstname();
        $data['LastName'] = $order->getCustomerLastName();
        $customerId = $order->getCustomerId();
        $response= $this->_api->orderData($data, $status, $name);
        if ($customerId){
            $item = $this->modelData;
            $item->setCustomerId($customerId);
            $item->setSyncWithMarketo(1);
            $item->save();
        }
        $res= json_decode($response, true);
        if ($res['success']!=1) {
            return $this;
        }
        $gKey = 'grazitti_maginate/orderconfig/';
        $productobjectname= $this->scopeConfig->getValue($gKey.'productcustomobject', $scope);
        $orderobjectname= $this->scopeConfig->getValue($gKey.'ordercustomobject', $scope);
        $status = 1;
        $oname= $orderobjectname;
        
        $tdiscount = explode('-', $order["base_discount_amount"]);
        $couponApplied = $order->getCouponCode();
        $odata['orderId']= $order->getIncrementId();
        $odata['orderStatus']= $order->getStatusLabel();
        $odata['emailAddress'] = $order->getCustomerEmail();
        $odata['totalOrderAmount'] = $order->getGrandTotal();
        $odata['shippingCost'] =$order["shipping_amount"];
        $odata['totalQuantity'] =(int)$order["total_qty_ordered"];
        $purchasedOrderDate = $order->getCreatedAt();
        $odata['orderDate'] = $purchasedOrderDate;
        $purchasedFromA = [];
        $purchasedFromA[] = $order->getStore()->getWebsite()->getName();
        $purchasedFromA[] = $order->getStore()->getGroup()->getName();
        $purchasedFromA[] = $order->getStore()->getName();
        $purchasedFrom = implode(' - ', $purchasedFromA);
        $odata['purchasedFrom'] = $purchasedFrom;
        $billingObj = $order->getBillingAddress();
        $billStreet = $billingObj->getStreet();
        if (isset($billStreet[1])) {
            $billStreetComplete = $billStreet[0].' '.$billStreet[1];
        } else {
            $billStreetComplete = $billStreet[0];
        }
        $billThirdStreet ='';
        if (isset($billStreet[2])) {
                $billThirdStreet = ' '.$billStreet[2];
        }
        $billobjectregion='';
        if ($billingObj->getRegion()) {
            $billobjectregion = ', '.$billingObj->getRegion();
        }
        $billobjectcompany='';
        if ($billingObj->getCompany()) {
            $billobjectcompany = trim($billingObj->getCompany()).', ';
        }
        
        $countryCode = $billingObj->getCountryId();
        $countryName = $this->_countryFactory->create()->loadByCode($countryCode);
        $billobjectcountry='';
        if ($countryName->getName()) {
            $billobjectcountry = ', '.$countryName->getName();
        }
        $odata['billingAddress'] =  $billobjectcompany.$billStreetComplete.$billThirdStreet.', ';
        $odata['billingAddress'] .= $billingObj->getCity().$billobjectregion.$billobjectcountry.', ';
        $odata['billingAddress'] .= $billingObj->getPostcode().'; '.'T: '.$billingObj->getTelephone();
        
        $shippingObj = $order->getShippingAddress();
        $shipStreetComplete='';
        if (!empty($shippingObj)) {
            $shipStreet = $shippingObj->getStreet();
            if (isset($shipStreet[1])) {
                $shipStreetComplete = $shipStreet[0].' '.$shipStreet[1];
            } else {
                $shipStreetComplete = $shipStreet[0];
            }
            $shipThirdStreet ='';
            if (isset($shipStreet[2])) {
                $shipThirdStreet = ' '.$shipStreet[2];
            }
            $shipobjectcompany='';
            if ($shippingObj->getCompany()) {
                $shipobjectcompany = trim($shippingObj->getCompany()).', ';
            }
            $shipobjectregion='';
            if ($shippingObj->getRegion()) {
                $shipobjectregion = ', '.$shippingObj->getRegion();
            }
            $shippingCountryCode = $shippingObj->getCountryId();
            $shippingCountryName = $this->_countryFactory->create()->loadByCode($shippingCountryCode);
            $shipobjectcountry='';
            if ($shippingCountryName->getName()) {
                $shipobjectcountry = ', '.$shippingCountryName->getName();
            }
            $odata['shippingAddress'] = $shipobjectcompany.$shipStreetComplete.$shipThirdStreet.', ';
            $odata['shippingAddress'] .= $shippingObj->getCity().$shipobjectregion.$shipobjectcountry.', ';
            $odata['shippingAddress'] .= $shippingObj->getPostcode().'; '.'T: '.$shippingObj->getTelephone();
        }
        
        $odata['couponApplied'] = $couponApplied ;
        if ($order["tax"]) {
                    $odata['taxInOrder'] =$order["tax"] ;
        } else {
            $odata['taxInOrder'] =0.00 ;
        }
        if ($order->getBaseDiscountAmount()) {
            $tdiscount = explode('-', $order->getBaseDiscountAmount());
            if ($tdiscount[0]) {
                $odata['totalOrderDiscount'] = $tdiscount[0];
            } elseif ($tdiscount[1]) {
                $odata['totalOrderDiscount'] = $tdiscount[1];
            }
        } else {
            $odata['totalOrderDiscount'] = 0.00;
        }
               
        $response= $this->_api->orderData($odata, $status, $oname);
        
        $resp= json_decode($response, true);
        if ($resp['success']==1) {
            foreach ($order->getAllVisibleItems() as $item) {
                if ($item->getData()) {
                    $digits = 3;
                    $rndno= rand(pow(10, $digits-1), pow(10, $digits)-1);
                    $cname= $productobjectname;
                    $pdata['emailAddress'] = $order->getCustomerEmail();
                    $pdata['orderId']= $order->getIncrementId();
                
                    if ($item->getProductType() == 'configurable') {
                        $sku = $item->getProductOptions()['simple_sku'];
                        $productItems = $this->productRepository->get($sku);
                        $enID = $productItems->getEntityId();
                        $parentByChild = $this->_catalogProductTypeConfigurable->getParentIdsByChild($enID);
                        if (isset($parentByChild[0])) {
                             $parentId = $parentByChild[0];
                        }
                        $parentProductName= $this->product->create()->load($parentId);
                        $pdata['incrementId'] = $order->getId().$productItems->getEntityId();
                        $pdata['productId'] = $productItems->getEntityId();
                        $pdata['productName'] = $parentProductName->getName();
                        $pdata['productAmount'] = $productItems->getPrice();
                    } else {
                        $proID = $item->getProductId();
                        $parentByChild = $this->_catalogProductTypeConfigurable->getParentIdsByChild($proID);
                        if (isset($parentByChild[0])) {
                             $parentId = $parentByChild[0];
                             $parentProductId= $this->product->create()->load($parentId);
                             $productName = $parentProductId->getName();
                        } else {
                            $productName = $item->getName();
                        }
                        $simplePrice = $this->productRepository->get($item->getSku());
                        $pdata['incrementId'] = $order->getId().$item->getProductId();
                        $pdata['productId'] = $item->getProductId();
                        $pdata['productName'] = $productName;
                        $pdata['productAmount'] = $simplePrice->getPrice();
                    }
                    $pdata['productSku'] = $item->getSku();
                    $pdata['productQty'] = (int)$item->getQtyOrdered();
                    $pdata['productStatus'] = $item->getStatus();
                    $response = $this->_api->orderData($pdata, $status, $cname);
                
                }
            }
            $order->setSyncWithMarketo(1);
            $order->save();
            
        }
        return $this;
    }
}
