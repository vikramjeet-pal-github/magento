<?php
/*
NOTICE OF LICENSE

This source file is subject to the NekloEULA that is bundled with this package in the file ICENSE.txt.

It is also available through the world-wide-web at this URL: http://store.neklo.com/LICENSE.txt

Copyright (c)  Neklo (http://store.neklo.com/)
*/


namespace Neklo\XTableRate\Model\ResourceModel\Carrier\Tablerate\CSV;

use Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate\CSV\ColumnNotFoundException;

class ColumnResolver extends \Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate\CSV\ColumnResolver
{
    const COLUMN_NAME = 'Shipping Name';

    /**
     * @var array
     */
    private $nameToPositionIdMap = [
        self::COLUMN_COUNTRY            => 0,
        self::COLUMN_REGION             => 1,
        self::COLUMN_ZIP                => 2,
        self::COLUMN_NAME               => 3,
        self::COLUMN_WEIGHT             => 4,
        self::COLUMN_WEIGHT_DESTINATION => 4,
        self::COLUMN_PRICE              => 5,
    ];

    /**
     * @var array
     */
    private $headers;

    /**
     * ColumnResolver constructor.
     *
     * @param array $headers
     * @param array $columns
     */
    public function __construct(array $headers, array $columns = [])
    {
        $this->nameToPositionIdMap = array_merge($this->nameToPositionIdMap, $columns);
        $this->headers = array_map('trim', $headers);
    }

    /**
     * @param string $column
     * @param array  $values
     *
     * @return string|int|float|null
     * @throws ColumnNotFoundException
     */
    public function getColumnValue($column, array $values)
    {
        $log = \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class);
        $column = (string)$column;
        $columnIndex = array_search($column, $this->headers, true);
        if (false === $columnIndex) {
            $log->debug($column);
            if (array_key_exists($column, $this->nameToPositionIdMap)) {
                $columnIndex = $this->nameToPositionIdMap[$column];
            } else {
                throw new ColumnNotFoundException(__('Requested column "%1" cannot be resolved', $column));
            }
        }

        if (!array_key_exists($columnIndex, $values)) {
            throw new ColumnNotFoundException(__('Column "%1" not found', $column));
        }

        return trim($values[$columnIndex]);
    }
}
