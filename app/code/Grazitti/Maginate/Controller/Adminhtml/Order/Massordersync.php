<?php
/**
 * Copyright © 2020 Grazitti. All rights reserved.
 */
namespace Grazitti\Maginate\Controller\Adminhtml\Order;

use Magento\Framework\Stdlib\DateTime;
use Grazitti\Maginate\Model\Orderapi;
use Magento\Framework\App\Config\ScopeConfigInterface;

use \Magento\Backend\App\Action\Context;
use \Magento\Framework\App\ResponseInterface;
use \Magento\Framework\Controller\ResultFactory;
use \Magento\Framework\Controller\ResultInterface;
use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use \Magento\Ui\Component\MassAction\Filter;
use \Magento\Sales\Model\ResourceModel\Order\CollectionFactory;


class Massordersync extends \Magento\Backend\App\Action 
{
        /**
         * Massordersync Action for Mass Sync of Order with Marketo
         * @return Void
         * */
    protected $_api;
    protected $scopeConfig;
    protected $_countryFactory;
	protected $filter;
	protected $collectionFactory;
    const XML_PATH_LEAD_INTEGRATION = 'grazitti_maginate/orderconfig/maginate_order_integration';
    
    public function __construct(
		\Magento\Directory\Model\CountryFactory $countryFactory,
        Orderapi $Api,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\Order $orderData,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Grazitti\Maginate\Helper\Data $dataHelper,
        Context $context,
		CollectionFactory $collectionFactory,
		Filter $filter
        
    ) {
		$this->filter = $filter;
		$this->collectionFactory = $collectionFactory;
        $this->_api = $Api;
        $this->date = $date;
        $this->scopeConfig = $scopeConfig;
        $this->jsonHelper = $jsonHelper;
        $this->dataHelper = $dataHelper;
        $this->orderData = $orderData;
        $this->_countryFactory = $countryFactory;
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $this->_leadIntegration=$this->scopeConfig->getValue(self::XML_PATH_LEAD_INTEGRATION, $storeScope);
        parent::__construct($context);
    }
    public function execute()
    {
        $expiry  = $this->dataHelper->checkExpiry() ;
        if ($this->_leadIntegration && $expiry) {
				$collection = $this->filter->getCollection($this->collectionFactory->create());
                $orderIds = $collection->getAllIds();
                $name='';
                $status='';
                $mergeData = [];
			if (!is_array($orderIds)) {
                $this->messageManager->addError(
                    __('Please select item(s)')
                );
            }else{			
				$cusCount = count($orderIds);
				if ($cusCount > 200) {
					$this->messageManager->addError(
						__('Cannot select more than 200 item(s)')
					);
				} else {	
					foreach ($orderIds as $orderId) {
							$order = $this->orderData->load($orderId);
							$order->setSyncWithMarketo(1);
							$order->save();
							$data['Email'] = $order->getCustomerEmail();
							$data['FirstName'] = $order->getCustomerFirstname();
							$data['LastName'] = $order->getCustomerLastName();
							array_push($mergeData, $data);
					}
					try {
						$count = count($mergeData);
						$response=$this->_api->postmassData($mergeData, $status, $name);
						$res= json_decode($response, true);
						$split_orderIds = array_chunk($orderIds, 50);
						foreach ($split_orderIds as $split_orderId) {
							$this->massOrdersyncAction($split_orderId);
						}
							$this->messageManager->addSuccess(
								__('Orders have been synced successfully with Marketo')
							);
					} catch (\Exception $e) {
									$this->messageManager->addError(
										__($e->getMessage())
									);
					}
				}
			}
		}
		$resultRedirect = $this->resultRedirectFactory->create();
		$resultRedirect->setRefererOrBaseUrl();
		return $resultRedirect;
    }
    public function massOrdersyncAction($split_orderId)
    {
            $mergedProductData = [];
        $mergedOrderData = [];
        foreach ($split_orderId as $orderId) {
            $order = $this->orderData->load($orderId);
            if ($order->getId()) {
                        
                        $odata['orderId']= $order->getIncrementId();
                        $odata['orderStatus']= $order->getStatusLabel();
                        $odata['emailAddress'] = $order->getCustomerEmail();
                        $odata['totalOrderAmount'] =$order->getGrandTotal();
                        $odata['shippingCost'] =$order["shipping_amount"] ;
                        $odata['totalQuantity'] = (int)($order["total_qty_ordered"]);
                        $purchasedOrderDate = $order->getCreatedAt();
                        $purchasedFromA = [];
                        $purchasedFromA[] = $order->getStore()->getWebsite()->getName();
                        $purchasedFromA[] = $order->getStore()->getGroup()->getName();
                        $purchasedFromA[] = $order->getStore()->getName();
                        $purchasedFrom = implode(' - ', $purchasedFromA);
                        $odata['orderDate'] = $purchasedOrderDate;
                        $odata['purchasedFrom'] = $purchasedFrom;
                        $billingObj = $order->getBillingAddress();
                        $billStreet = $billingObj->getStreet();
                        
                $billobjectregion='';
                if ($billingObj->getRegion()) {
                    $billobjectregion = ', '.$billingObj->getRegion();
                }
                $billStreetComplete ='';
                if (isset($billStreet[1])) {
                    $billStreetComplete = $billStreet[0].' '.$billStreet[1];
                } else {
                    $billStreetComplete = $billStreet[0];
                }
                $billobjectcompany='';
                if ($billingObj->getCompany()) {
                    $billobjectcompany = trim($billingObj->getCompany()).', ';
                }
                $billThirdStreet='';
                if (isset($billStreet[2])) {
                    $billThirdStreet = ' '.$billStreet[2];
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
                if (!empty($shippingObj)) {
                    $shipobjectcompany='';
                    if ($shippingObj->getCompany()) {
                        $shipobjectcompany = trim($shippingObj->getCompany()).', ';
                    }
                    $shipStreet = $shippingObj->getStreet();
                    if (isset($shipStreet[1])) {
                            $shipStreetComplete = $shipStreet[0].' '.$shipStreet[1];
                    } else {
                        $shipStreetComplete = $shipStreet[0];
                    }
                    $shipobjectregion='';
                    if ($shippingObj->getRegion()) {
                        $shipobjectregion = ', '.$shippingObj->getRegion();
                    }
                    
                    $shipThirdStreet ='';
                    if (isset($shipStreet[2])) {
                        $shipThirdStreet = ' '.$shipStreet[2];
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
                if ($order["tax"]) {
                            $odata['taxInOrder'] =$order[tax] ;
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
                        array_push($mergedOrderData, $odata);
                        
                foreach ($order->getAllVisibleItems() as $item) {
                            $status = 1;
                    if ($item->getData('has_children')==1) {
                        continue;
                    } else {
                        $pdata['emailAddress'] = $order->getCustomerEmail();
                        $pdata['orderId']= $order->getIncrementId();
                        $pdata['incrementId'] = $order->getId().$item->getProductId();
                        $pdata['productId'] = $item->getProductId();
                        $pdata['productName'] = $item->getName();
                        $pdata['productAmount'] = $item->getPrice();
                        $pdata['productSku'] = $item->getSku();
                        $pdata['productQty'] = (int)$item->getQtyOrdered();
                        $pdata['productStatus'] = $item->getStatus();
                        array_push($mergedProductData, $pdata);
                    }
                }
            }
        }
        $sStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $productobjectname= $this->scopeConfig->getValue('grazitti_maginate/orderconfig/productcustomobject', $sStore);
        $orderobjectname= $this->scopeConfig->getValue('grazitti_maginate/orderconfig/ordercustomobject', $sStore);
        $cname=$productobjectname;
        $oname= $orderobjectname;
        $order_response = $this->_api->postmassData($mergedOrderData, $status, $oname);
        $product_response = $this->_api->postmassData($mergedProductData, $status, $cname);
        
        $order->setSyncedWithMarketo(1);
        $order->save();
    }
}
