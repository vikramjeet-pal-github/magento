<?php
/*
NOTICE OF LICENSE

This source file is subject to the NekloEULA that is bundled with this package in the file ICENSE.txt.

It is also available through the world-wide-web at this URL: http://store.neklo.com/LICENSE.txt

Copyright (c)  Neklo (http://store.neklo.com/)
*/


namespace Neklo\XTableRate\Model\ResourceModel\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate\RateQueryFactory;
use Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate as CarrierTablerate;

class Tablerate extends CarrierTablerate
{
    /**
     * Define main table and id field name
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('neklo_xtablerate', 'pk');
    }

    protected function _saveImportData(array $data)
    {
        $this->logger->debug(var_export($data, true));
        if (!empty($data)) {
            $columns = [
                'website_id',
                'dest_country_id',
                'dest_region_id',
                'dest_zip',
                'shipping_name',
                'condition_name',
                'condition_value',
                'price',
            ];
            $this->logger->debug(var_export([$columns, $data], true));
            $this->getConnection()->insertArray($this->getMainTable(), $columns, $data);
            $this->_importedRows += count($data);
        }

        return $this;
    }

    public function getRate(RateRequest $request)
    {
        $adapter = $this->getConnection();
        $bind = [
            ':website_id' => (int)$request->getWebsiteId(),
            ':country_id' => $request->getDestCountryId(),
            ':region_id'  => (int)$request->getDestRegionId(),
            ':postcode'   => $request->getDestPostcode(),
        ];

        $select = $adapter->select()
            ->from($this->getMainTable())
            ->where('website_id = :website_id')
            ->order(
                [
                    'dest_country_id DESC',
                    'dest_region_id DESC',
                    'dest_zip DESC',
                    'condition_value DESC',
                ]
            );

        $conditionList = [
            "dest_country_id = :country_id AND dest_region_id = :region_id AND dest_zip = :postcode",
            "dest_country_id = :country_id AND dest_region_id = :region_id AND dest_zip = ''",

            // Handle asterix in dest_zip field
            "dest_country_id = :country_id AND dest_region_id = :region_id AND dest_zip = '*'",
            "dest_country_id = :country_id AND dest_region_id = 0 AND dest_zip = '*'",
            "dest_country_id = '0' AND dest_region_id = :region_id AND dest_zip = '*'",
            "dest_country_id = '0' AND dest_region_id = 0 AND dest_zip = '*'",

            "dest_country_id = :country_id AND dest_region_id = 0 AND dest_zip = ''",
            "dest_country_id = :country_id AND dest_region_id = 0 AND dest_zip = :postcode",
            "dest_country_id = :country_id AND dest_region_id = 0 AND dest_zip = '*'",
        ];

        // Render destination condition
        $orWhere = '(' . join(') OR (', $conditionList) . ')';
        $select->where($orWhere);

        // Render condition by condition name
        if (is_array($request->getConditionName())) {
            $orWhere = [];
            $index = 0;
            foreach ($request->getConditionName() as $conditionName) {
                $bindNameKey = sprintf(':condition_name_%d', $index);
                $bindValueKey = sprintf(':condition_value_%d', $index);
                $orWhere[] = "(condition_name = {$bindNameKey} AND condition_value <= {$bindValueKey})";
                $bind[$bindNameKey] = $conditionName;
                $bind[$bindValueKey] = $request->getData($conditionName);
                $index++;
            }

            if ($orWhere) {
                $select->where(implode(' OR ', $orWhere));
            }
        } else {
            $bind[':condition_name'] = $request->getConditionName();
            $bind[':condition_value'] = $request->getData($request->getConditionName());
            $select->where('condition_name = :condition_name');
            $select->where('condition_value <= :condition_value');
        }

        // Normalize destination zip code
        $shippingNames = [];
        $result = [];
        $rates = $adapter->fetchAll($select, $bind);
        if ($rates) {
            foreach ($rates as $key => $row) {
                if (array_search($row['shipping_name'], $shippingNames) === false) {
                    $shippingNames[] = $row['shipping_name'];
                    $result[] = $row;
                }
            }
            foreach ($rates as $key => $row) {
                if ($row['dest_zip'] == '*') {
                    $rates[$key]['dest_zip'] = '';
                }
            }
        }

        return $result;
    }

    public function getShippingNames()
    {
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
            ->distinct(true)
            ->from($this->getMainTable(), ['shipping_name'])
            ->order(['shipping_name ASC']);
        $rows = $adapter->fetchAll($select);
        $shippingNames = [];
        foreach ($rows as $row) {
            $shippingNames[] = $row['shipping_name'];
        }

        return $shippingNames;
    }
}
