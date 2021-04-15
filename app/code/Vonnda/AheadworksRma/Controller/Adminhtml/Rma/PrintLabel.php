<?php
namespace Vonnda\AheadworksRma\Controller\Adminhtml\Rma;

use Magento\Backend\App\Action\Context;
use Vonnda\AheadworksRma\Model\ResourceModel\Package\CollectionFactory;
use Magento\Shipping\Model\Shipping\LabelGenerator;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;

class PrintLabel extends \Magento\Backend\App\Action
{

    /** @var CollectionFactory */
    protected $packageCollectionFactory;

    /** @var LabelGenerator */
    protected $labelGenerator;

    /** @var FileFactory */
    protected $fileFactory;

    /**
     * @param Context $context
     * @param CollectionFactory $packageCollectionFactory
     * @param LabelGenerator $labelGenerator
     * @param FileFactory $fileFactory
     */
    public function __construct(
        Context $context,
        CollectionFactory $packageCollectionFactory,
        LabelGenerator $labelGenerator,
        FileFactory $fileFactory
    ) {
        $this->packageCollectionFactory = $packageCollectionFactory;
        $this->labelGenerator = $labelGenerator;
        $this->fileFactory = $fileFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $requestId = $this->getRequest()->getParam('request_id');
        try {
            $packages = $this->packageCollectionFactory->create()->addFieldToFilter('request_id', $requestId);
            if ($packages->count() > 0) {
                $pdf = new \Zend_Pdf();
                foreach ($packages as $package) {
                    $labelContent = $package->getShippingLabel();
                    if ($labelContent) {
                        if (stripos($labelContent, '%PDF-') !== false) {
                            throw new LocalizedException(__('Packages have not saved correctly. Please save the RMA request, then try again.'));
                        } else {
                            $page = $this->labelGenerator->createPdfPageFromImageString($labelContent);
                            if (!$page) {
                                $this->messageManager->addError(__('We don\'t recognize or support the file extension in this package: %1.', $package->getPackageId()));
                            }
                            $pdf->pages[] = $page;
                        }
                    } else {
                        throw new LocalizedException(__('Packages have not saved correctly. Please save the RMA request, then try again.'));
                    }
                }
                $pdfContent = [
                    'type' => 'string',
                    'value' => $pdf->render(),
                    'rm' => true
                ];
                return $this->fileFactory->create('ShippingLabel('.$requestId.').pdf', $pdfContent, DirectoryList::VAR_DIR, 'application/pdf');
            } else {
                throw new LocalizedException(__('Packages have not saved correctly. Please save the RMA request, then try again.'));
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while creating shipping label.'));
        }
        $this->_redirect('aw_rma_admin/rma/edit', ['id' => $requestId]);
    }

}