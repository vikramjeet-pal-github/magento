<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */
namespace DEG\CustomReports\Block\Adminhtml\Report;

use Magento\Framework\DataObject;
use Magento\Framework\Registry;

class DemandCustomGrid extends \Magento\Backend\Block\Widget\Grid
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * Grid constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param Registry $registery
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);
        $this->registry = $registry;
    }

    public function _prepareLayout()
    {
        /** @var $customReport \DEG\CustomReports\Model\CustomReport */
        $customReport = $this->registry->registry('current_customreport');
        /** @var $genericCollection \DEG\CustomReports\Model\GenericReportCollection */
        $genericCollection = $customReport->getGenericReportCollection();
        $columnList = $this->getColumnListFromCollection($genericCollection);
        if (isset($columnList) && count($columnList->getData())) {
            $this->addColumnSet($columnList);
            $this->addGridExportBlock();
            $this->setCollection($genericCollection);
        }
        parent::_prepareLayout();
    }

    public function getColumnListFromCollection($collection)
    {
        $columnsCollection = clone $collection;
        $columnsCollection->getSelect()->limitPage(1, 1);
        $item = $columnsCollection->getFirstItem();
        return $item;
    }

    /**
     * @param $dataItem
     */
    public function addColumnSet($dataItem)
    {
        /** @var $columnSet \Magento\Backend\Block\Widget\Grid\ColumnSet **/
        $columnSet = $this->_layout->createBlock(
            'Magento\Backend\Block\Widget\Grid\ColumnSet',
            'deg_customreports_grid.grid.columnSet'
        );

        foreach ($dataItem->getData() as $key => $val) {
            if ($this->_defaultSort === false) {
                $this->_defaultSort = $key;
            }

            $type = 'text';

            if (is_numeric($val)) {
                $type = "number";
            }

            if (strtotime($val)) {
                $type = "date";
            }

            /** @var $column \Magento\Backend\Block\Widget\Grid\Column **/
            $data = [
                'data' => [
                    'index' => $key,
                    'type' => $type
                ]
            ];

            if ($type == "date") {
                $data['data']['timezone'] = false;
            }

            $column = $this->_layout->createBlock(
                'Magento\Backend\Block\Widget\Grid\Column',
                'deg_customreports_grid.grid.column.' . str_replace(" ", "_", strtolower($key)),
                $data
            );

            $columnSet->setChild($key, $column);
        }

        $this->_backendSession->unsIsReportExport();

        $this->setChild('grid.columnSet', $columnSet);
    }

    /**
     * Add the export block as a child block to the grid.
     *
     * @return $this
     */
    public function addGridExportBlock()
    {
        $exportArguments = [
            'data' => [
                'exportTypes'=> [
                    'csv' => [
                        'urlPath' => '*/*/exportCsv',
                        'label' => 'CSV'
                    ]
                ]
            ]
        ];

        $customReport = $this->registry->registry('current_customreport');

        $exportBlock = $this->_layout->createBlock('DEG\CustomReports\Block\Adminhtml\Report\DemandCustomExport', 'deg_customreports_grid.grid.export', $exportArguments);
        $this->setChild('grid.export', $exportBlock);
        $exportBlock->lazyPrepareLayout();
        return $this;
    }
}
