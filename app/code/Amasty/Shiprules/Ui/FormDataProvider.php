<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Ui;

use Magento\Framework\App\Request\DataPersistorInterface;
use Amasty\Shiprules\Model\ResourceModel\Rule\CollectionFactory;
use Amasty\Shiprules\Model\Rule;

/**
 * Data Provider for amasty_shiprules_form.
 */
class FormDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var array
     */
    protected $loadedData;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);

        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
    }

    /**
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        /** @var Rule $rule */
        foreach ($items as $rule) {
            $this->loadedData[$rule->getId()] = $rule->prepareForEdit()->getData();
        }

        $data = $this->dataPersistor->get(\Amasty\Shiprules\Model\ConstantsInterface::DATA_PERSISTOR_FORM);

        if (!empty($data)) {
            $rule = $this->collection->getNewEmptyItem();
            $rule->setData($data);
            $this->loadedData[$rule->getId()] = $rule->getData();
            $this->dataPersistor->clear(\Amasty\Shiprules\Model\ConstantsInterface::DATA_PERSISTOR_FORM);
        }

        return $this->loadedData;
    }
}
