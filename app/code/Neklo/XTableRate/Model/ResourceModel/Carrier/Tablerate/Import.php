<?php
/*
NOTICE OF LICENSE

This source file is subject to the NekloEULA that is bundled with this package in the file ICENSE.txt.

It is also available through the world-wide-web at this URL: http://store.neklo.com/LICENSE.txt

Copyright (c)  Neklo (http://store.neklo.com/)
*/


namespace Neklo\XTableRate\Model\ResourceModel\Carrier\Tablerate;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\File\ReadInterface;
use Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate\CSV\ColumnResolver;
use Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate\CSV\ColumnResolverFactory;
use Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate\CSV\RowException;
use Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate\CSV\RowParser;
use Magento\Store\Model\StoreManagerInterface;

class Import extends \Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate\Import
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ScopeConfigInterface
     */
    private $coreConfig;

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var CSV\RowParser
     */
    private $rowParser;

    /**
     * @var ColumnResolverFactory
     */
    private $columnResolverFactory;

    /**
     * @var DataHashGenerator
     */
    private $dataHashGenerator;

    /**
     * @var array
     */
    private $uniqueHash = [];
    /**
     * @var \Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate
     */
    private $carrierTablerate;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Array of condition full names
     *
     * @var array
     */
    protected $_conditionFullNames = [];

    public function __construct(
        StoreManagerInterface $storeManager,
        Filesystem $filesystem,
        ScopeConfigInterface $coreConfig,
        RowParser $rowParser,
        ColumnResolverFactory $columnResolverFactory,
        \Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate\DataHashGenerator $dataHashGenerator,
        \Magento\OfflineShipping\Model\Carrier\Tablerate $carrierTablerate,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct(
            $storeManager,
            $filesystem,
            $coreConfig,
            $rowParser,
            $columnResolverFactory,
            $dataHashGenerator
        );
        $this->storeManager = $storeManager;
        $this->filesystem = $filesystem;
        $this->coreConfig = $coreConfig;
        $this->rowParser = $rowParser;
        $this->columnResolverFactory = $columnResolverFactory;
        $this->dataHashGenerator = $dataHashGenerator;
        $this->carrierTablerate = $carrierTablerate;
        $this->logger = $logger;
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return (bool)count($this->getErrors());
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->rowParser->getColumns();
    }

    public function getData(ReadInterface $file, $websiteId, $conditionShortName, $conditionFullName, $bunchSize = 5000)
    {
        $this->errors = [];

        try {
            $headers = $this->getHeaders($file);
        } catch (LocalizedException $e) {
            $headers = [];
        }

        /** @var ColumnResolver $columnResolver */
        $columnResolver = $this->columnResolverFactory->create(['headers' => $headers]);

        $rowNumber = 1;
        $items = [];
        while (false !== ($csvLine = $file->readCsv())) {
            try {
                $rowNumber++;
                if (empty($csvLine)) {
                    continue;
                }

                $rowData = $this->rowParser->parse(
                    $csvLine,
                    $rowNumber,
                    $websiteId,
                    $conditionShortName,
                    $conditionFullName,
                    $columnResolver
                );

                // protect from duplicate
                $hash = $this->dataHashGenerator->getHash($rowData);
                if (array_key_exists($hash, $this->uniqueHash)) {
                    throw new RowException(
                        __(
                            'Duplicate Row #%1 (duplicates row #%2)',
                            $rowNumber,
                            $this->uniqueHash[$hash]
                        )
                    );
                }
                $this->uniqueHash[$hash] = $rowNumber;

                $items[] = $rowData;
                if (count($items) === $bunchSize) {
                    yield $items;
                    $items = [];
                }
            } catch (RowException $e) {
                $this->errors[] = $e->getMessage();
            }
        }

        if (!empty($items)) {
            yield $items;
        }
    }

    /**
     * Return import condition full name by condition name code
     *
     * @param string $conditionName
     *
     * @return string
     */
    protected function _getConditionFullName($conditionName)
    {
        if (!isset($this->_conditionFullNames[$conditionName])) {
            $name = $this->carrierTablerate->getCode('condition_name_short', $conditionName);
            $this->_conditionFullNames[$conditionName] = $name;
        }

        return $this->_conditionFullNames[$conditionName];
    }

    /**
     * @param ReadInterface $file
     *
     * @return array
     * @throws LocalizedException
     */
    private function getHeaders(ReadInterface $file)
    {
        // check and skip headers
        $headers = $file->readCsv();
        if ($headers === false || count($headers) < 6) {
            throw new LocalizedException(__('Please correct Table Rates File Format.'));
        }

        return $headers;
    }
}
