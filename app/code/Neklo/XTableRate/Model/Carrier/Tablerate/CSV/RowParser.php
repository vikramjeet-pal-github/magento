<?php
/*
NOTICE OF LICENSE

This source file is subject to the NekloEULA that is bundled with this package in the file ICENSE.txt.

It is also available through the world-wide-web at this URL: http://store.neklo.com/LICENSE.txt

Copyright (c)  Neklo (http://store.neklo.com/)
*/


namespace Neklo\XTableRate\Model\Carrier\Tablerate\CSV;

use Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate\LocationDirectory;
use Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate\CSV\RowException;
use Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate\CSV\ColumnNotFoundException;

class RowParser
{
    /**
     * @var LocationDirectory
     */
    private $locationDirectory;

    /**
     * RowParser constructor.
     *
     * @param LocationDirectory $locationDirectory
     */
    public function __construct(LocationDirectory $locationDirectory)
    {
        $this->locationDirectory = $locationDirectory;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return [
            'website_id',
            'dest_country_id',
            'dest_region_id',
            'dest_zip',
            'shipping_name',
            'condition_name',
            'condition_value',
            'price',
        ];
    }

    /**
     * @param array          $rowData
     * @param int            $rowNumber
     * @param int            $websiteId
     * @param string         $conditionShortName
     * @param string         $conditionFullName
     * @param ColumnResolver $columnResolver
     *
     * @return array
     * @throws ColumnNotFoundException
     * @throws RowException
     */
    public function parse(
        array $rowData,
        $rowNumber,
        $websiteId,
        $conditionShortName,
        $conditionFullName,
        ColumnResolver $columnResolver
    ) {
        // validate row
        if (count($rowData) < 5) {
            throw new RowException(__('Please correct Table Rates format in the Row #%1.', $rowNumber));
        }

        $countryId = $this->getCountryId($rowData, $rowNumber, $columnResolver);
        $regionId = $this->getRegionId($rowData, $rowNumber, $columnResolver, $countryId);
        $zipCode = $this->getZipCode($rowData, $columnResolver);
        $conditionValue = $this->getConditionValue($rowData, $rowNumber, $conditionFullName, $columnResolver);
        $price = $this->getPrice($rowData, $rowNumber, $columnResolver);
        $shippingName = $this->getShippingName($rowData, $columnResolver);

        return [
            'website_id'      => trim($websiteId),
            'dest_country_id' => trim($countryId),
            'dest_region_id'  => trim($regionId),
            'dest_zip'        => trim($zipCode),
            'shipping_name'   => trim($shippingName),
            'condition_name'  => trim($conditionShortName),
            'condition_value' => trim($conditionValue),
            'price'           => trim($price),
        ];
    }

    /**
     * @param array          $rowData
     * @param int            $rowNumber
     * @param ColumnResolver $columnResolver
     *
     * @return null|string
     * @throws ColumnNotFoundException
     * @throws RowException
     */
    private function getCountryId(array $rowData, $rowNumber, ColumnResolver $columnResolver)
    {
        $countryCode = $columnResolver->getColumnValue(ColumnResolver::COLUMN_COUNTRY, $rowData);
        // validate country
        if ($this->locationDirectory->hasCountryId($countryCode)) {
            $countryId = $this->locationDirectory->getCountryId($countryCode);
        } elseif ($countryCode === '*' || $countryCode === '') {
            $countryId = '0';
        } else {
            throw new RowException(__('Please correct Country "%1" in the Row #%2.', $countryCode, $rowNumber));
        }

        return $countryId;
    }

    /**
     * @param array          $rowData
     * @param int            $rowNumber
     * @param ColumnResolver $columnResolver
     * @param int            $countryId
     *
     * @return int|string
     * @throws ColumnNotFoundException
     * @throws RowException
     */
    private function getRegionId(array $rowData, $rowNumber, ColumnResolver $columnResolver, $countryId)
    {
        $regionCode = $columnResolver->getColumnValue(ColumnResolver::COLUMN_REGION, $rowData);
        if ($countryId !== '0' && $this->locationDirectory->hasRegionId($countryId, $regionCode)) {
            $regionId = $this->locationDirectory->getRegionId($countryId, $regionCode);
        } elseif ($regionCode === '*' || $regionCode === '') {
            $regionId = 0;
        } else {
            throw new RowException(__('Please correct Region/State "%1" in the Row #%2.', $regionCode, $rowNumber));
        }

        return $regionId;
    }

    /**
     * @param array          $rowData
     * @param ColumnResolver $columnResolver
     *
     * @return float|int|null|string
     * @throws ColumnNotFoundException
     */
    private function getShippingName(array $rowData, ColumnResolver $columnResolver)
    {
        return $columnResolver->getColumnValue(ColumnResolver::COLUMN_NAME, $rowData);
    }

    /**
     * @param array          $rowData
     * @param ColumnResolver $columnResolver
     *
     * @return float|int|null|string
     * @throws ColumnNotFoundException
     */
    private function getZipCode(array $rowData, ColumnResolver $columnResolver)
    {
        $zipCode = $columnResolver->getColumnValue(ColumnResolver::COLUMN_ZIP, $rowData);
        if ($zipCode === '') {
            $zipCode = '*';
        }

        return $zipCode;
    }

    /**
     * @param array          $rowData
     * @param int            $rowNumber
     * @param string         $conditionFullName
     * @param ColumnResolver $columnResolver
     *
     * @return bool|float
     * @throws ColumnNotFoundException
     * @throws RowException
     */
    private function getConditionValue(array $rowData, $rowNumber, $conditionFullName, ColumnResolver $columnResolver)
    {
        // validate condition value
        $conditionValue = $columnResolver->getColumnValue($conditionFullName, $rowData);
        $value = $this->_parseDecimalValue($conditionValue);
        if ($value === false) {
            throw new RowException(
                __(
                    'Please correct %1 "%2" in the Row #%3.',
                    $conditionFullName,
                    $conditionValue,
                    $rowNumber
                )
            );
        }

        return $value;
    }

    /**
     * @param array          $rowData
     * @param int            $rowNumber
     * @param ColumnResolver $columnResolver
     *
     * @return bool|float
     * @throws ColumnNotFoundException
     * @throws RowException
     */
    private function getPrice(array $rowData, $rowNumber, ColumnResolver $columnResolver)
    {
        $priceValue = $columnResolver->getColumnValue(ColumnResolver::COLUMN_PRICE, $rowData);
        $price = $this->_parseDecimalValue($priceValue);
        if ($price === false) {
            throw new RowException(__('Please correct Shipping Price "%1" in the Row #%2.', $priceValue, $rowNumber));
        }

        return $price;
    }

    /**
     * Parse and validate positive decimal value
     * Return false if value is not decimal or is not positive
     *
     * @param  string $value
     *
     * @return bool|float
     */
    private function _parseDecimalValue($value)
    {
        $result = false;
        if (is_numeric($value)) {
            $value = (double)sprintf('%.4F', $value);
            if ($value >= 0.0000) {
                $result = $value;
            }
        }

        return $result;
    }
}
