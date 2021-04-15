<?php

namespace Vonnda\AlternativeOrigin\Model;

use Magento\Framework\Api\DataObjectHelper;
use Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesInterface;
use Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesInterfaceFactory;

class AlternativeShippingOriginZones extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var AlternativeShippingOriginZonesInterfaceFactory
     */
    protected $alternativeShippingOriginZonesDataFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var string
     */
    protected $_eventPrefix = 'alternative_shipping_origin_zones';

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param AlternativeShippingOriginZonesInterfaceFactory $alternativeShippingOriginZonesDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param \Vonnda\AlternativeOrigin\Model\ResourceModel\AlternativeShippingOriginZones $resource
     * @param \Vonnda\AlternativeOrigin\Model\ResourceModel\AlternativeShippingOriginZones\Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        AlternativeShippingOriginZonesInterfaceFactory $alternativeShippingOriginZonesDataFactory,
        DataObjectHelper $dataObjectHelper,
        \Vonnda\AlternativeOrigin\Model\ResourceModel\AlternativeShippingOriginZones $resource,
        \Vonnda\AlternativeOrigin\Model\ResourceModel\AlternativeShippingOriginZones\Collection $resourceCollection,
        array $data = []
    ) {
        $this->alternativeShippingOriginZonesDataFactory = $alternativeShippingOriginZonesDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve alternative_shipping_origin_zones model with alternative_shipping_origin_zones data
     * @return AlternativeShippingOriginZonesInterface
     */
    public function getDataModel()
    {
        $alternativeShippingOriginZonesData = $this->getData();

        $alternativeShippingOriginZonesDataFactory = $this->alternativeShippingOriginZonesDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $alternativeShippingOriginZonesDataFactory,
            $alternativeShippingOriginZonesData,
            AlternativeShippingOriginZonesInterface::class
        );

        return $alternativeShippingOriginZonesData;
    }
}
