<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_ShippingTableRates
 */


namespace Amasty\ShippingTableRates\Model\Import\Rate\Behaviors;

use Amasty\Base\Model\Import\Behavior\BehaviorInterface;
use Amasty\ShippingTableRates\Model\RateFactory;
use Amasty\ShippingTableRates\Model\RateRepository;
use Amasty\ShippingTableRates\Model\Import\Rate\Renderer;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\App\Request\Http as Request;

class Add implements BehaviorInterface
{
    /**
     * @var RateFactory
     */
    private $rateFactory;

    /**
     * @var RateRepository
     */
    private $rateRepository;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Renderer
     */
    private $renderer;

    public function __construct(
        RateFactory $rateFactory,
        RateRepository $rateRepository,
        Request $request,
        Renderer $renderer
    ) {
        $this->rateFactory = $rateFactory;
        $this->rateRepository = $rateRepository;
        $this->request = $request;
        $this->renderer = $renderer;
    }

    /**
     * @param array $importData
     * @return DataObject
     */
    public function execute(array $importData)
    {
        $resultImportObject = new DataObject();
        $shippingMethodId = $this->request->getPost('amastrate_method');

        foreach ($importData as $rateData) {
            $rate = $this->rateFactory->create();
            $rateData = $this->renderer->renderRateData($rateData);
            $rate->setData($rateData);
            $rate->setMethodId($shippingMethodId);
            try {
                $this->rateRepository->save($rate);
            } catch (CouldNotSaveException $e) {
                return null;
            }
        }

        $resultImportObject->setCountItemsCreated(count($importData));

        return $resultImportObject;
    }
}
