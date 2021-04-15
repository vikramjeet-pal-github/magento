<?php
namespace Vonnda\AheadworksRma\Controller\Rma;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Aheadworks\Rma\Model\RequestRepository;
use Vonnda\AheadworksRma\Model\ResourceModel\Package\CollectionFactory;
use Magento\Shipping\Model\Shipping\LabelGenerator;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;

class PrintLabel extends \Magento\Framework\App\Action\Action
{

    /** @var SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /** @var RequestRepository */
    private $requestRespository;

    /** @var CollectionFactory */
    protected $packageCollectionFactory;

    /** @var LabelGenerator */
    protected $labelGenerator;

    /** @var FileFactory */
    protected $fileFactory;

    /**
     * @param Context $context
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestRepository $requestRespository
     * @param CollectionFactory $packageCollectionFactory
     * @param LabelGenerator $labelGenerator
     * @param FileFactory $fileFactory
     */
    public function __construct(
        Context $context,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestRepository $requestRespository,
        CollectionFactory $packageCollectionFactory,
        LabelGenerator $labelGenerator,
        FileFactory $fileFactory
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->requestRespository = $requestRespository;
        $this->packageCollectionFactory = $packageCollectionFactory;
        $this->labelGenerator = $labelGenerator;
        $this->fileFactory = $fileFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $requestId = $this->getRequest()->getParam('request_id');
        try {
            $this->searchCriteriaBuilder->addFilter('increment_id', $requestId);
            $requests = $this->requestRespository->getList($this->searchCriteriaBuilder->create())->getItems();
            $request = array_shift($requests);
            $packages = $this->packageCollectionFactory->create()->addFieldToFilter('request_id', $request->getId());
            if ($packages->count() > 0) {
                $pdf = new \Zend_Pdf();
                foreach ($packages as $package) {
                    $labelContent = $package->getShippingLabel();
                    if ($labelContent) {
                        if (stripos($labelContent, '%PDF-') !== false) {
                            throw new LocalizedException(__('There was an issue generating your shipping label. Please contact customer service.'));
                        } else {
                            $page = $this->labelGenerator->createPdfPageFromImageString($labelContent);
                            if (!$page) {
                                $this->messageManager->addErrorMessage(__('There was in creating the shipping label for package ID %1. Please contact customer service.', $package->getPackageId()));
                            }
                            $pdf->pages[] = $page;
                        }
                    } else {
                        throw new LocalizedException(__('There was an issue generating your shipping label. Please contact customer service.'));
                    }
                }
                $pdfContent = [
                    'type' => 'string',
                    'value' => $pdf->render(),
                    'rm' => true
                ];
                return $this->fileFactory->create('ShippingLabel('.$requestId.').pdf', $pdfContent, DirectoryList::VAR_DIR, 'application/pdf');
            } else {
                throw new LocalizedException(__('There was an issue generating your shipping label. Please contact customer service.'));
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while creating shipping label.'));
        }
        $this->_redirect('customer/account');
    }

}