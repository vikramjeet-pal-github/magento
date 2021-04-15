<?php

namespace DEG\CustomReports\Controller\Adminhtml\CustomReport;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class Index
 */
class ExportCsv extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'DEG_CustomReports::customreports_export_report';
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $_fileFactory;

    private $builder;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \DEG\CustomReports\Controller\Adminhtml\CustomReport\Builder $builder

    ) {
        $this->_fileFactory = $fileFactory;
        $this->builder = $builder;

        parent::__construct($context);
    }

    /**
     * Export customer grid to CSV format
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        if (extension_loaded('newrelic')) {
            newrelic_ignore_transaction();
        }
        
        $customReport = $this->builder->build($this->getRequest());

        $this->_view->loadLayout();
        $fileName = $customReport->getReportName() . '.csv';

        /** @var @var $reportGrid \DEG\CustomReports\Block\Adminhtml\Report\Grid */
        $reportGrid = $this->_view->getLayout()->createBlock(
            'DEG\CustomReports\Block\Adminhtml\Report\Grid',
            'report.grid'
        );
        $exportBlock = $reportGrid->getChildBlock('grid.export');
        return $this->_fileFactory->create(
            $fileName,
            $exportBlock->getCsvFile(),
            DirectoryList::VAR_DIR
        );
    }
}
