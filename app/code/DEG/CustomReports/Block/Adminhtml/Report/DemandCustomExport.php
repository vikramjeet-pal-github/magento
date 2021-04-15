<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */
namespace DEG\CustomReports\Block\Adminhtml\Report;

use Carbon\Carbon;

use Magento\Backend\Block\Widget\Grid\Export;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DemandCustomExport extends Export
{
    public function _prepareLayout()
    {
        return $this;
    }

    /**
     * Prepare export button
     *
     * This had to be implemented as a lazy prepare because if the export block is not added
     * through the layout, there is no way for the _prepareLayout to work since the parent block
     * would not be set yet.
     *
     * @return $this
     */
    public function lazyPrepareLayout()
    {
        $this->setChild(
            'export_button',
            $this->getLayout()->createBlock(
                'Magento\Backend\Block\Widget\Button'
            )->setData(
                [
                    'label' => __('Export'),
                    'onclick' => $this->getParentBlock()->getJsObjectName() . '.doExport()',
                    'class' => 'task',
                ]
            )
        );
        return parent::_prepareLayout();
    }

    /**
     * Retrieve a file container array by grid data as CSV
     *
     * Return array with keys type and value
     *
     * @return array
     */
    public function getDemandCsvFile()
    {
        $name = md5(microtime());
        $file = $this->_path . '/' . $name . '.csv';

        $this->_directory->create($this->_path);
        $stream = $this->_directory->openFile($file, 'w+');

        $this->exportDemandCollection($stream);

        $stream->unlock();
        $stream->close();

        return [
            'type' => 'filename',
            'value' => $file,
            'rm' => true  // can delete file after use
        ];
    }

    /**
     * Iterate collection and call callback method per item
     * For callback method first argument always is item object
     *
     * @param string $callback
     * @param array $args additional arguments for callback method
     * @return void
     */
    public function exportDemandCollection($stream)
    {
        /** @var $originalCollection \Magento\Framework\Data\Collection */
        $originalCollection = $this->getParentBlock()->getPreparedCollection();

        $count = null;
        $page = 1;
        $lPage = null;
        $break = false;

        $fullCollection = [];
        while ($break !== true) {
            $originalCollection->clear();
            $originalCollection->setPageSize($this->getExportPageSize());
            $originalCollection->setCurPage($page);
            $originalCollection->load();
            if ($count === null) {
                $count = $originalCollection->getSize();
                $lPage = $originalCollection->getLastPageNumber();
            }
            if ($lPage == $page) {
                $break = true;
            }
            $page++;

            $collection = $this->_getRowCollection($originalCollection);
            //Build out full combined collection 
            foreach ($collection as $item) {
                $fullCollection[] = $item;
            }
        }

        $startDateArray = $this->getStartDateArray($fullCollection);
        $planStartDate = $this->getFirstOrNull($startDateArray);
        $planMaxStartDate = $this->getMaximumStartDate($fullCollection);
        $planEndDate = $this->getMaximumEndDate($fullCollection);
        
        $periodType = $this->getPeriodType($fullCollection);
        if($periodType === 'Weekly'){
            $fullDateArr = $this->getAllDatesBetweenInterval($planStartDate, $planMaxStartDate, 1, "week");
        } elseif($periodType === 'Monthly'){
            $fullDateArr = $this->getAllDatesBetweenInterval($planStartDate, $planMaxStartDate, 1, "month");
        }

        $stream->writeCsv($this->getCustomHeaders($fullCollection, $fullDateArr));
        $stream->lock();

        $skus = $this->getUniqueSkus($fullCollection);
        foreach($skus as $sku){
            $row = $this->generateRowDataForSku($sku, $planStartDate, $planMaxStartDate, $fullDateArr, $fullCollection);
            $stream->writeCsv($row);
        }
    }

    /**
     * Get custom header row
     *
     * @return string[]
     */
    protected function getCustomHeaders($fullCollection, $dateArray)
    {
        $periodType = $this->getPeriodType($fullCollection);

        $row = ["Internal Id", 
                "Item (Req)", 
                "Subsidiary (Req)",
                "Location (Req)",
                "Memo", 
                "Plan Start Date (Req)",
                "Plan End Date",
                "View (Req)"];
        
        foreach($dateArray as $date){
            $row[] = $periodType . " Quantity:" . $this->formatDateForDemand($date);
        }

        return $row;
    }

    /**
     * Assumes key is "SKU"
     * 
     * @param array $fullCollection
     * @return array $skus
     */
    public function getUniqueSkus($fullCollection)
    {
        $skus = $this->pluckNonNull($fullCollection, 'SKU');
        $skus = array_merge(array_unique($skus));
        return $skus;
    }

    /**
     * Assumes type will be correctly set in "View" Column
     * 
     * @param array $fullCollection
     * @return string
     */
    public function getPeriodType($fullCollection)
    {
        if(!isset($fullCollection[0])){
            return null;
        }

        return $fullCollection[0]['View'];
    }

    /**
     * Reformats dateString for header row
     *
     * @param string
     * @return string
     */
    public function formatDateForDemand($dateString)
    {
        $arr = explode("-", $dateString);
        $shouldAppendZero = strlen($arr[1]) < 2 && (int)$arr[1] < 10;
        $month =  $shouldAppendZero ? ("0" . $arr[1]) : $arr[1];
        return $month . "/" . $arr[2] . "/" . $arr[0];
    }

    /**
     * Reformats dateString for header row
     *
     * @param string
     * @return string
     */
    public function formatDateForDemandSingleDig($dateString)
    {
        $arr = explode("-", $dateString);
        return (int)$arr[1] . "/" . (int)$arr[2] . "/" . $arr[0];
    }

    /**
     * 
     * @param array
     * @return string
     */
    public function getMaximumEndDate($fullCollection)
    {
        $endDates = $this->pluckNonNull($fullCollection, 'plan_end_date');
        if(count($endDates) === 0){
            return null;
        }

        $endDates = $this->sortDemandDates($endDates, 'DESC');
        return $endDates[0];
    }

    /**
     * 
     * @param array
     * @return string
     */
    public function getMaximumStartDate($fullCollection)
    {
        $startDates = $this->pluckNonNull($fullCollection, 'plan_start_date');
        if(count($startDates) === 0){
            return null;
        }

        $startDates = $this->sortDemandDates($startDates, 'DESC');
        return $startDates[0];
    }

    /**
     * 
     * Returns sorted and unique start dates for the entire collection
     * 
     * @param array $fullCollection
     * @return array $startDates
     */
    public function getStartDateArray($fullCollection)
    {
        $startDates = $this->pluckNonNull($fullCollection, 'plan_start_date');
        $startDates = array_unique($startDates);
        return $this->sortDemandDates($startDates);
    }

    /**
     * 
     * Dates expected to come in 'Y-m-d' format
     * 
     * @param array $dates
     * @param string $order
     * @return array $dates
     */
    public function sortDemandDates($dates, $order='ASC')
    {
        uasort($dates, function($a, $b) use ($order){
            if($a === $b){
                return 0;
            }

            $dateOne = Carbon::createFromFormat('Y-m-d', $a);
            $dateTwo = Carbon::createFromFormat('Y-m-d', $b);

            if($order === 'ASC'){
                return $dateOne < $dateTwo ? -1 : 1;
            } elseif($order === 'DESC'){
                return $dateOne > $dateTwo ? -1 : 1;
            }
        });

        return array_merge($dates);
    }

    /**
     * 
     * Generate a row for a given sku
     * 
     * @param string $sku
     * @param string $planStartDate
     * @param string $planEndDate
     * @param array $dateArray
     * @return array $fullCollection
     */
    public function generateRowDataForSku($sku, $planStartDate, $planEndDate, $dateArray, $fullCollection)
    {
        //Get simple fields
        $periodType = $this->getPeriodType($fullCollection);
        $staticRowData = ["", $sku, "Molekule, Inc.", "", $periodType . " Import"];

        $startAndEndDate = [
            $this->formatDateForDemandSingleDig($planStartDate),
            $this->formatDateForDemandSingleDig($planEndDate)
        ] ;

        $row = array_merge($staticRowData, $startAndEndDate, [$periodType]);

        //Insert quantities
        foreach($dateArray as $date){
            //Get sku specific collection
            $dataForSku = array_filter($fullCollection, function($item) use ($sku){
                if($item['SKU'] === $sku){
                    return true;
                }
                return false;
            });

            $itemHasQuantityForDate = false;
            foreach($dataForSku as $item){
                $date = $this->normalizeDate($date);
                $startDate = $this->normalizeDate($item['plan_start_date']);
                if($date === $startDate){
                    $row[] = $item['Quantity'];
                    $itemHasQuantityForDate = true;
                    break;
                }
            }

            if(!$itemHasQuantityForDate){
                $row[] = "0";
            }
        }

        return $row;
    }

    /**
     * 
     * Fixes inconsistencies in formatting
     * 
     * @param string $dateString
     * @return string $date
     */
    public function normalizeDate($dateString)
    {
        $arr = explode("-", $dateString);
        $shouldAppendZero = strlen($arr[1]) < 2 && (int)$arr[1] < 10;
        $month =  $shouldAppendZero ? ("0" . $arr[1]) : $arr[1];
        return $arr[0] . "-" . $month . "-" . $arr[2];
    }

    /**
     * 
     * @param array $arr
     * @return mixed
     */
    public function getFirstOrNull($arr)
    {
        return isset($arr[0]) ? $arr[0] : null;
    }

    /**
     * 
     * @param array $objArray
     * @return array $subFieldArray
     */
    public function pluckNonNull($objArray, $field)
    {
        $subFieldArray = [];
        foreach($objArray as $item){
            if(isset($item[$field]) && $item[$field]){
                $subFieldArray[] = $item[$field];
            }
        }

        return $subFieldArray;
    }

    /**
     * 
     * Expects date format from demand, will not overflow with months
     * 
     * @param string $startDate
     * @param string $endDate
     * @param int $frequency
     * @return string $unit
     */
    public function getAllDatesBetweenInterval($startDate, $endDate, $frequency, $unit)
    {
        $dateArray = [$this->normalizeDate($startDate)];
        $endDateObj = Carbon::createFromFormat('Y-m-d', $endDate);
        $dateIterator = Carbon::createFromFormat('Y-m-d', $startDate);
        
        if($unit === 'month'){
            $dateIterator->settings(['monthOverflow' => false]);
        }

        while($dateIterator < $endDateObj){
            $dateIterator->add($frequency, $unit);
            $dateArray[] = $dateIterator->format('Y-m-d');
        }

        return $dateArray;
    }
}
