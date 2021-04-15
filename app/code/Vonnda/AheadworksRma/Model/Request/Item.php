<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\AheadworksRma\Model\Request;

use Vonnda\AheadworksRma\Api\Data\RequestItemInterface;
use Vonnda\AheadworksRma\Model\ResourceModel\Package\CollectionFactory AS PackageCollectionFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Sales\Api\OrderItemRepositoryInterface;

/**
 * Class Item
 */
class Item extends \Aheadworks\Rma\Model\Request\Item implements RequestItemInterface
{
    protected $orderItemRepository;

    protected $orderItem;

    /**
     * @var PackageCollectionFactory
     */
    protected $packageCollectionFactory;


    public function __construct(
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $attributeValueFactory,
        OrderItemRepositoryInterface $orderItemRepository,
        PackageCollectionFactory $packageCollectionFactory,
        $data = []
    ) {
        $this->orderItemRepository = $orderItemRepository;
        $this->packageCollectionFactory = $packageCollectionFactory;
        parent::__construct(
            $extensionFactory,
            $attributeValueFactory,
            $data
        );
    }

    public function getRequestId()
    {
        return $this->_get('request_id');
    }

    public function setRequestId($requestId)
    {
        return $this->setData('request_id', $requestId);
    }

    /**
     * {@inheritdoc}
     */
    public function getItemSku()
    {
        $orderItem = $this->getOrderItem();
        if($orderItem){
            return $orderItem->getSku();
        }
        return null;
    }

    protected function getOrderItem()
    {
        if(!isset($this->orderItem)){
            if($this->getItemId()){
                $this->orderItem = $this->orderItemRepository->get($this->getItemId());
            }
        }
        return $this->orderItem;
    }

     /**
     * Get Location
     *
     * @return string|null
     */
    public function getLocation()
    {
        $packageCollection = $this->packageCollectionFactory->create();
        $requestId =  $this->_get('request_id');
        $orderItem = $this->getOrderItem();
        $package = $packageCollection
            ->addFieldToFilter('order_id', ['eq' => $orderItem->getOrderId()])
            ->addFieldToFilter('item_id', ['eq' => $orderItem->getItemId()])
            ->addFieldToFilter('request_id', ['eq' => $requestId])
            ->getFirstItem();
        if($package){
            return $package->getLocation();
        }
        return null;
    }
}
