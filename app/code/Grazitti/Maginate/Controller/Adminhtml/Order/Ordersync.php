<?php
/**
 * Copyright © 2020 Graziiti. All rights reserved.
 */
namespace Grazitti\Maginate\Controller\Adminhtml\Order;

use Magento\Framework\Stdlib\DateTime;
use Grazitti\Maginate\Model\Orderapi;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Ordersync extends \Magento\Backend\App\Action
{
    /**
     * Ordersync Action for Syncing of order with Marketo
     * @return Void
     * */
    protected $_api;
    protected $scopeConfig;
    protected $_leadIntegration;
    protected $_countryFactory;
    const XML_PATH_LEAD_INTEGRATION = 'grazitti_maginate/orderconfig/maginate_order_integration';
    
    public function __construct(
        Orderapi $Api,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Grazitti\Maginate\Helper\Data $dataHelper,
        \Grazitti\Maginate\Model\Orderapi $postdata,
        \Magento\Sales\Model\Order $orderData,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Directory\Model\CountryFactory $countryFactory
    ) {
        $this->_api = $Api;
        $this->date = $date;
        $this->scopeConfig = $scopeConfig;
        $this->jsonHelper = $jsonHelper;
        $this->dataHelper = $dataHelper;
        $this->orderData = $orderData;
        $this->postdata = $postdata;
        $this->_countryFactory = $countryFactory;
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $this->_leadIntegration=$this->scopeConfig->getValue(self::XML_PATH_LEAD_INTEGRATION, $storeScope);
        parent::__construct($context);
    }
    public function execute()
    {
        $expiry  = $this->dataHelper->checkExpiry();
        if (!$this->_leadIntegration || !$expiry) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setRefererOrBaseUrl();
            return $resultRedirect;
        }
        $orderId = $this->getRequest()->getParam('entity_id');
        $order = $this->orderData->load($orderId);
        $status = 0;
        $name='';
        $data['Email'] = $order->getCustomerEmail();
        $data['FirstName'] = $order->getCustomerFirstname();
        $data['LastName'] = $order->getCustomerLastName();
        $response= $this->postdata->orderData($data, $status, $name);

        $res= json_decode($response, true);
        if ($res['success']) {
            $sStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $configKey = 'grazitti_maginate/orderconfig/';
            $productobjectname= $this->scopeConfig->getValue($configKey.'productcustomobject', $sStore);
            $orderobjectname= $this->scopeConfig->getValue($configKey.'ordercustomobject', $sStore);
            $status = 1;
            try {
                $oname= $orderobjectname;
                $odata['orderId']= $order->getIncrementId();
                $odata['orderStatus']= $order->getStatusLabel();
                $odata['emailAddress'] = $order->getCustomerEmail();
                $odata['totalOrderAmount'] = $order->getGrandTotal();
                if ($order->getShippingAmount()) {
                    $odata['shippingCost'] = $order->getShippingAmount();
                } else {
                    $odata['shippingCost'] = 0.00;
                }

                $orderItems = $order->getAllItems();
                $total_qty = 0;
                foreach ($orderItems as $item) {
                    $total_qty = $total_qty + $item->getQtyOrdered();
                }
                if ($total_qty) {
                    $odata['totalQuantity'] = (int)($total_qty);
                }
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
                $billThirdStreet='';
                if (isset($billStreet[2])) {
                    $billThirdStreet = ' '.$billStreet[2];
                }
                if (isset($billStreet[1])) {
                    $billStreetComplete = $billStreet[0].' '.$billStreet[1];
                } else {
                    $billStreetComplete = $billStreet[0];
                }
                $billobjectregion='';
                if ($billingObj->getRegion()) {
                    $billobjectregion = ', '.$billingObj->getRegion();
                }
                $countryCode = $billingObj->getCountryId();
                $countryName = $this->_countryFactory->create()->loadByCode($countryCode);
                
                $billobjectcompany='';
                if ($billingObj->getCompany()) {
                    $billobjectcompany = trim($billingObj->getCompany()).', ';
                }
                
                $billobjectcountry='';
                if ($countryName->getName()) {
                    $billobjectcountry = ', '.$countryName->getName();
                }
                $odata['billingAddress'] =  $billobjectcompany.$billStreetComplete.$billThirdStreet.', ';
                $odata['billingAddress'] .=  $billingObj->getCity().$billobjectregion.$billobjectcountry.', ';
                $odata['billingAddress'] .=  $billingObj->getPostcode().'; '.'T: '.$billingObj->getTelephone();
                $shipStreetComplete ='';
                $shippingObj = $order->getShippingAddress();
                if (!empty($shippingObj)) {
                    $shipStreet = $shippingObj->getStreet();
                    $shipThirdStreet ='';
                    if (isset($shipStreet[2])) {
                        $shipThirdStreet = ' '.$shipStreet[2];
                    }
                    
                    if (isset($shipStreet[1])) {
                        $shipStreetComplete = $shipStreet[0].' '.$shipStreet[1];
                    } else {
                        $shipStreetComplete = $shipStreet[0];
                    }
                    $shipobjectregion='';
                    if ($shippingObj->getRegion()) {
                        $shipobjectregion = ', '.$shippingObj->getRegion();
                    }
                    $shipobjectcompany='';
                    if ($shippingObj->getCompany()) {
                        $shipobjectcompany = trim($shippingObj->getCompany()).', ';
                    }
                    
                    $shippingCountryCode = $shippingObj->getCountryId();
                    $shippingCountryName = $this->_countryFactory->create()->loadByCode($shippingCountryCode);
                    $shipobjectcountry='';
                    if ($shippingCountryName->getName()) {
                        $shipobjectcountry = ', '.$shippingCountryName->getName();
                    }
                    $odata['shippingAddress'] = $shipobjectcompany.$shipStreetComplete.$shipThirdStreet.', ';
                    $odata['shippingAddress'] .= $shippingObj->getCity().$shipobjectregion.$shipobjectcountry.', ';
                    $odata['shippingAddress'] .= $shippingObj->getPostcode().'; ';
                    $odata['shippingAddress'] .= 'T: '.$shippingObj->getTelephone();
                }

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

                $response= $this->postdata->orderData($odata, $status, $oname);
                $resp= json_decode($response, true);
                if ($resp['success']==1) {
                    foreach ($order->getAllVisibleItems() as $item) {
                        if ($item->getData('has_children')==1) {
                            continue;
                        } else {
                            $digits = 3;
                            $rndno= rand(pow(10, $digits-1), pow(10, $digits)-1);
                            $cname= $productobjectname;
                            $pdata['emailAddress'] = $order->getCustomerEmail();
                            $pdata['orderId']= $order->getIncrementId();
                            $pdata['incrementId'] = $order->getId().$item->getProductId();
                            $pdata['productId'] = $item->getProductId();
                            $pdata['productName'] = $item->getName();
                            $pdata['productAmount'] = $item->getPrice();
                            $pdata['productSku'] = $item->getSku();
                            $pdata['productQty'] = (int)$item->getQtyOrdered();
                            $pdata['productStatus'] = $item->getStatus();
                            $response = $this->postdata->orderData($pdata, $status, $cname);
                        }
                    }
                    $order->setSyncWithMarketo(1);
                    $order->save();
                }

                $this->messageManager->addSuccess(
                    __('Order has been synced successfully with Marketo')
                );
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('We can\'t process your request right now. Sorry, that\'s all we know.')
                );
            }
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setRefererOrBaseUrl();
        return $resultRedirect;
    }
}
