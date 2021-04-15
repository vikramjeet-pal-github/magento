<?php

namespace Grazitti\Warranty\Api;

interface SetAddressInterface
{
  
  /**
     * 
     * @param string $entity_id
     * @param string $product_sku
     * @return string
     */
    public function setAddress($entity_id,$product_sku);

    

}