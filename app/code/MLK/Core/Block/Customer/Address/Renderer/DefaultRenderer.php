<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MLK\Core\Block\Customer\Address\Renderer;

use Magento\Customer\Model\Address\Mapper;
use Magento\Customer\Model\Metadata\ElementFactory;
use Magento\Customer\Block\Address\Renderer\DefaultRenderer as CoreDefaultRenderer;
use Magento\Framework\View\Element\Context;
use Magento\Directory\Model\CountryFactory;
use Magento\Customer\Api\AddressMetadataInterface;

use Magento\Directory\Model\RegionFactory;
use Magento\Customer\Api\Data\RegionInterface;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;


/**
 * Address format renderer default
 */
class DefaultRenderer extends CoreDefaultRenderer
{
    
    protected $regionFactory;

    protected $regionDataFactory;

    protected $dataObjectHelper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Context $context
     * @param ElementFactory $elementFactory
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Customer\Api\AddressMetadataInterface $metadataService
     * @param Mapper $addressMapper
     * @param array $data
     */
    public function __construct(
        Context $context,
        ElementFactory $elementFactory,
        CountryFactory $countryFactory,
        AddressMetadataInterface $metadataService,
        Mapper $addressMapper,
        array $data = [],
        RegionFactory $regionFactory,
        RegionInterfaceFactory $regionDataFactory,
        DataObjectHelper $dataObjectHelper
    ) {
        $this->regionFactory = $regionFactory;
        $this->regionDataFactory = $regionDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;

        parent::__construct($context,
                            $elementFactory,
                            $countryFactory,
                            $metadataService,
                            $addressMapper,
                            $data);
    }

    /**
     * Get region data
     *
     * @param int $regionId
     * @return \Magento\Customer\Api\Data\RegionInterface $region
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getRegionInterface($regionId)
    {
        $regionCode = '';
        $regionName = '';
        if ($regionId) {
            $newRegion = $this->regionFactory->create()->load($regionId);
            $regionCode = $newRegion->getCode();
            $regionName = $newRegion->getDefaultName();
        }

        $regionData = [
            RegionInterface::REGION_ID => $regionId ? $regionId : null,
            RegionInterface::REGION => $regionName ? $regionName : null,
            RegionInterface::REGION_CODE => $regionCode
                ? $regionCode
                : null,
        ];

        $region = $this->regionDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $region,
            $regionData,
            \Magento\Customer\Api\Data\RegionInterface::class
        );
        return $region;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function renderArray($addressAttributes, $format = null)
    {
        //This can be a systems config
        $shortRegionCode = true;
        
        switch ($this->getType()->getCode()) {
            case 'html':
                $dataFormat = ElementFactory::OUTPUT_FORMAT_HTML;
                break;
            case 'pdf':
                $dataFormat = ElementFactory::OUTPUT_FORMAT_PDF;
                break;
            case 'oneline':
                $dataFormat = ElementFactory::OUTPUT_FORMAT_ONELINE;
                break;
            default:
                $dataFormat = ElementFactory::OUTPUT_FORMAT_TEXT;
                break;
        }

        $attributesMetadata = $this->_addressMetadataService->getAllAttributesMetadata();
        $data = [];
        foreach ($attributesMetadata as $attributeMetadata) {
            if (!$attributeMetadata->isVisible()) {
                continue;
            }
            $attributeCode = $attributeMetadata->getAttributeCode();
            if ($attributeCode == 'country_id' && isset($addressAttributes['country_id'])) {
                $data['country'] = $this->_countryFactory->create()->loadByCode(
                    $addressAttributes['country_id']
                )->getName();
            } elseif ($attributeCode == 'region' && isset($addressAttributes['region'])) {
                $region = $this->getRegionInterface((int)$addressAttributes['region_id']);
                if($dataFormat === ElementFactory::OUTPUT_FORMAT_HTML && $shortRegionCode){
                    $data['region'] = __($region->getRegionCode());
                } else {
                    $data['region'] = __($addressAttributes['region']);
                }
            } elseif (isset($addressAttributes[$attributeCode])) {
                $value = $addressAttributes[$attributeCode];
                $dataModel = $this->_elementFactory->create($attributeMetadata, $value, 'customer_address');
                $value = $dataModel->outputValue($dataFormat);
                if ($attributeMetadata->getFrontendInput() == 'multiline') {
                    $values = $dataModel->outputValue(ElementFactory::OUTPUT_FORMAT_ARRAY);
                    // explode lines
                    foreach ($values as $k => $v) {
                        $key = sprintf('%s%d', $attributeCode, $k + 1);
                        $data[$key] = $v;
                    }
                }
                $data[$attributeCode] = $value;
            }
        }
        if ($this->getType()->getEscapeHtml()) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->escapeHtml($value);
            }
        }
        $format = $format !== null ? $format : $this->getFormatArray($addressAttributes);
        return $this->filterManager->template($format, ['variables' => $data]);
    }
}
