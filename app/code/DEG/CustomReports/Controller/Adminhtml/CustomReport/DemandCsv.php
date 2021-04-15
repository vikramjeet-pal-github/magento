<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */
namespace DEG\CustomReports\Controller\Adminhtml\CustomReport;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class DemandCsv
 */
class DemandCsv extends \Magento\Backend\App\Action
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
        $customReport = $this->builder->build($this->getRequest());

        $this->_view->loadLayout();
        $fileName = $customReport->getReportName() . '.csv';

        $this->_session->setIsReportExport(true);

        /** @var @var $reportGrid \DEG\CustomReports\Block\Adminhtml\Report\Grid */
        $reportGrid = $this->_view->getLayout()->createBlock(
            'DEG\CustomReports\Block\Adminhtml\Report\DemandCustomGrid',
            'report.grid'
        );

        $exportBlock = $reportGrid->getChildBlock('grid.export');

        return $this->_fileFactory->create(
            $fileName,
            $exportBlock->getDemandCsvFile(),
            DirectoryList::VAR_DIR
        );
    }
}
