<?php

namespace Vonnda\DeviceManager\Model;

use Vonnda\DeviceManager\Api\Data\DeviceManagerInterface;
use Magento\Framework\Api\DataObjectHelper;
use Vonnda\DeviceManager\Api\Data\DeviceManagerInterfaceFactory;

class DeviceManager extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'vonnda_devicemanager_devicemanager';

    /**
     * @var DeviceManagerInterfaceFactory
     */
    protected $devicemanagerDataFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param DeviceManagerInterfaceFactory $devicemanagerDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param \Vonnda\DeviceManager\Model\ResourceModel\DeviceManager $resource
     * @param \Vonnda\DeviceManager\Model\ResourceModel\DeviceManager\Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        DeviceManagerInterfaceFactory $devicemanagerDataFactory,
        DataObjectHelper $dataObjectHelper,
        \Vonnda\DeviceManager\Model\ResourceModel\DeviceManager $resource,
        \Vonnda\DeviceManager\Model\ResourceModel\DeviceManager\Collection $resourceCollection,
        array $data = []
    ) {
        $this->devicemanagerDataFactory = $devicemanagerDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve devicemanager model with devicemanager data
     * @return DeviceManagerInterface
     */
    public function getDataModel()
    {
        $devicemanagerData = $this->getData();
        
        $devicemanagerDataObject = $this->devicemanagerDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $devicemanagerDataObject,
            $devicemanagerData,
            DeviceManagerInterface::class
        );
        
        return $devicemanagerDataObject;
    }

}
