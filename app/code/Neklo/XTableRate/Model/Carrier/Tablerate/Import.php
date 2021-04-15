<?php
/*
NOTICE OF LICENSE

This source file is subject to the NekloEULA that is bundled with this package in the file ICENSE.txt.

It is also available through the world-wide-web at this URL: http://store.neklo.com/LICENSE.txt

Copyright (c)  Neklo (http://store.neklo.com/)
*/


namespace Neklo\XTableRate\Model\Carrier\Tablerate;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;
use Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate\CSV\RowException;
use Neklo\XTableRate\Model\Carrier\Tablerate\CSV\ColumnResolverFactory;
use Neklo\XTableRate\Model\Carrier\Tablerate\CSV\ColumnResolver;
use Neklo\XTableRate\Model\Carrier\Tablerate\CSV\RowParser;

class Import
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
     * @var RowParser
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

    public function __construct(
        StoreManagerInterface $storeManager,
        Filesystem $filesystem,
        ScopeConfigInterface $coreConfig,
        RowParser $rowParser,
        ColumnResolverFactory $columnResolverFactory,
        DataHashGenerator $dataHashGenerator
    ) {
        $this->storeManager = $storeManager;
        $this->filesystem = $filesystem;
        $this->coreConfig = $coreConfig;
        $this->rowParser = $rowParser;
        $this->columnResolverFactory = $columnResolverFactory;
        $this->dataHashGenerator = $dataHashGenerator;
    }

    /**
     * @param \Magento\Framework\Filesystem\File\ReadInterface $file
     *
     * @return array|bool
     * @throws LocalizedException
     */
    private function getHeaders(\Magento\Framework\Filesystem\File\ReadInterface $file)
    {
        // check and skip headers
        $headers = $file->readCsv();
        if ($headers === false || count($headers) < 6) {
            throw new LocalizedException(__('Please correct Table Rates File Format.'));
        }

        return $headers;
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

    /**
     * @param \Magento\Framework\Filesystem\File\ReadInterface $file
     * @param int                                              $websiteId
     * @param string                                           $conditionShortName
     * @param string                                           $conditionFullName
     * @param int                                              $bunchSize
     *
     * @return \Generator
     * @throws LocalizedException
     */
    public function getData(
        \Magento\Framework\Filesystem\File\ReadInterface $file,
        $websiteId,
        $conditionShortName,
        $conditionFullName,
        $bunchSize = 5000
    ) {
        $this->errors = [];

        $headers = $this->getHeaders($file);
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
}
