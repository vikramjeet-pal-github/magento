<?php

namespace Narvar\Accord\Plugin;

use Narvar\Accord\Helper\CustomLogger;
use Narvar\Accord\Helper\Processor;
use Narvar\Accord\Helper\Util;
use Narvar\Accord\Helper\NoFlakeLogger;

class InvoiceBase
{

    private $logger;

    private $processor;

    private $util;

    private $noFlakeLogger;

    public function __construct(
        CustomLogger $logger,
        Processor $processor,
        Util $util,
        NoFlakeLogger $noFlakeLogger
    ) {
        $this->logger            = $logger;
        $this->processor         = $processor;
        $this->util              = $util;
        $this->noFlakeLogger     = $noFlakeLogger;
    }

    public function afterSave($invoice)
    {
        $orderId = $invoice->getOrder()->getIncrementId();
        $storeId = $invoice->getOrder()->getStoreId();
        $eventName = 'narvar_invoice_plugin';
        try {
            $this->util->logMetadata($orderId, $storeId, $eventName, 'start');
            $retailerMoniker = $this->util->getRetailerMoniker($storeId);
            if (!empty($retailerMoniker)) {
                $narvarInvoiceObject = $this->util->getNarvarOrderObject(
                    $invoice->getOrder(),
                    $eventName
                );
                
                $narvarInvoiceObject['invoice'] = $this->util->getInvoiceData($invoice, $storeId);
                $this->processor->sendPluginData($narvarInvoiceObject, $retailerMoniker, $eventName);
                $this->noFlakeLogger->logNoFlakeData(
                    $narvarInvoiceObject,
                    $eventName,
                    $retailerMoniker
                );
            } else {
                $this->util->logMetadata(
                    $orderId,
                    $storeId,
                    $eventName,
                    'end - Narvar Accord Not Configured for this Store Id'
                );
            }
        } catch (\Exception $ex) {
            $this->util->handleException($ex, $orderId, $storeId, $eventName);
        } finally {
            return $invoice;
        }
    }
}
