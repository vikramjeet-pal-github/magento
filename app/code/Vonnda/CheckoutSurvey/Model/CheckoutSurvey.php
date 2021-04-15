<?php

namespace Vonnda\CheckoutSurvey\Model;

use Magento\Framework\Api\DataObjectHelper;
use Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterfaceFactory;
use Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterface;

class CheckoutSurvey extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'vonnda_checkoutsurvey';

    /**
     * @var CheckoutSurveyInterfaceFactory
     */
    protected $checkoutsurveyDataFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param CheckoutSurveyInterfaceFactory $checkoutsurveyDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param \Vonnda\CheckoutSurvey\Model\ResourceModel\CheckoutSurvey $resource
     * @param \Vonnda\CheckoutSurvey\Model\ResourceModel\CheckoutSurvey\Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        CheckoutSurveyInterfaceFactory $checkoutsurveyDataFactory,
        DataObjectHelper $dataObjectHelper,
        \Vonnda\CheckoutSurvey\Model\ResourceModel\CheckoutSurvey $resource,
        \Vonnda\CheckoutSurvey\Model\ResourceModel\CheckoutSurvey\Collection $resourceCollection,
        array $data = []
    ) {
        $this->checkoutsurveyDataFactory = $checkoutsurveyDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve checkoutsurvey model with checkoutsurvey data
     * @return CheckoutSurveyInterface
     */
    public function getDataModel()
    {
        $checkoutsurveyData = $this->getData();
        
        $checkoutsurveyDataObject = $this->checkoutsurveyDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $checkoutsurveyDataObject,
            $checkoutsurveyData,
            CheckoutSurveyInterface::class
        );
        
        return $checkoutsurveyDataObject;
    }
}
