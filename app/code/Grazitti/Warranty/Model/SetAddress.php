<?php
 
namespace Grazitti\Warranty\Model; 
 
class SetAddress implements \Grazitti\Warranty\Api\SetAddressInterface
{

    
   /**
     * 
     * @param string $entity_id
     * @param string $product_sku
     * @return string
     */
    public function setAddress($entity_id,$product_sku){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $orderAddress = $objectManager->create('MLK\Core\Model\Sales\Order');
        $collection = $orderAddress->getCollection();
        $collection->addFieldToFilter('entity_id',['eq'=>$entity_id]);


        foreach($collection as $item)
        {                       
           $item->setProductSku($product_sku);

        }
        $collection->save();
        return "Order Data Updated sucessfully";
        
    }
    
}