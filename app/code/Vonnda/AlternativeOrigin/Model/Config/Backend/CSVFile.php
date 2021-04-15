<?php

namespace Vonnda\AlternativeOrigin\Model\Config\Backend;

use Magento\Config\Model\Config\Backend\File;
use Magento\Framework\File\Csv;
use Magento\Framework\File\Uploader;
use Magento\Framework\Filesystem;
use Vonnda\AlternativeOrigin\Model\AlternativeShippingOriginZones;
use Vonnda\AlternativeOrigin\Model\AlternativeShippingOriginZonesRepository;
use Vonnda\AlternativeOrigin\Model\Data\AlternativeShippingOriginZones as AlternativeShippingOriginZonesData;
use Vonnda\AlternativeOrigin\Model\Data\AlternativeShippingOriginZonesFactory;

class CSVFile extends File
{
    /**
     * @var AlternativeShippingOriginZonesFactory
     */
    protected $alternativeShippingOriginZonesFactory;

    /**
     * @var AlternativeShippingOriginZones
     */
    protected $alternativeShippingOriginZonesModel;

    /**
     * @var AlternativeShippingOriginZonesRepository
     */
    protected $alternativeShippingOriginZonesRepository;

    /**
     * @var Csv
     */
    protected $fileReader;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;

    /**
     * CSVFile constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory
     * @param File\RequestData\RequestDataInterface $requestData
     * @param Filesystem $filesystem
     * @param AlternativeShippingOriginZonesFactory $alternativeShippingOriginZonesFactory
     * @param AlternativeShippingOriginZones $alternativeShippingOriginZonesModel
     * @param AlternativeShippingOriginZonesRepository $alternativeShippingOriginZonesRepository
     * @param Csv $fileReader
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Magento\Config\Model\Config\Backend\File\RequestData\RequestDataInterface $requestData,
        Filesystem $filesystem,
        AlternativeShippingOriginZonesFactory $alternativeShippingOriginZonesFactory,
        AlternativeShippingOriginZones $alternativeShippingOriginZonesModel,
        AlternativeShippingOriginZonesRepository $alternativeShippingOriginZonesRepository,
        \Magento\Framework\File\Csv $fileReader,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $uploaderFactory,
            $requestData,
            $filesystem,
            $resource,
            $resourceCollection,
            $data
        );

        $this->alternativeShippingOriginZonesFactory = $alternativeShippingOriginZonesFactory;
        $this->alternativeShippingOriginZonesModel = $alternativeShippingOriginZonesModel;
        $this->alternativeShippingOriginZonesRepository = $alternativeShippingOriginZonesRepository;
        $this->fileReader = $fileReader;
        $this->regionFactory = $regionFactory;
    }

    public function _getAllowedExtensions()
    {
        return ['csv'];
    }

    public function beforeSave()
    {
        $value = $this->getValue();
        $file = $this->getFileData();

        if (!empty($file)) {

            /**
             * Cleanup alternative_shipping_origin_zones tables before saving
             * @var AlternativeShippingOriginZones $alternativeOriginModel
             */
            $collection = $this->alternativeShippingOriginZonesModel->getCollection();
            $collection->walk('delete');

            $csvData = $this->fileReader->getData($file['tmp_name']);
            foreach ($csvData as $row => $data) {
                if ($row > 0) {
                    /** @var AlternativeShippingOriginZonesData $alternativeOriginModel */
                    $alternativeOriginData = $this->alternativeShippingOriginZonesFactory->create();
                    $alternativeOriginData->setCountryId(isset($data[0]) ? $data[0] : "");

                    $regionId = $this->getRegionIdByCode($data[0], $data[1]);

                    $alternativeOriginData->setRegionId($regionId);
                    $alternativeOriginData->setPostcode(isset($data[2]) ? $data[2] : "");
                    $this->alternativeShippingOriginZonesRepository->save($alternativeOriginData);
                }
            }

            $uploadDir = $this->_getUploadDir();
            try {
                /** @var Uploader $uploader */
                $uploader = $this->_uploaderFactory->create(['fileId' => $file]);
                $uploader->setAllowedExtensions($this->_getAllowedExtensions());
                $uploader->setAllowRenameFiles(true);
                $uploader->addValidateCallback('size', $this, 'validateMaxSize');
                $result = $uploader->save($uploadDir);
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(__('%1', $e->getMessage()));
            }

            $filename = $result['file'];
            if ($filename) {
                if ($this->_addWhetherScopeInfo()) {
                    $filename = $this->_prependScopeInfo($filename);
                }
                $this->setValue($filename);
            }
        } else {
            if (is_array($value) && !empty($value['delete'])) {
                $this->setValue('');
            } elseif (is_array($value) && !empty($value['value'])) {
                $this->setValue($value['value']);
            } else {
                $this->unsValue();
            }
        }

        return $this;
    }

    private function getRegionIdByCode($countryId, $code)
    {
        $region = $this->regionFactory->create()->loadByCode($code, $countryId)->getFirstItem();
        return $region->getId();
    }

}