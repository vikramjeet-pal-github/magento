<?php
namespace Grazitti\Warranty\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\QuoteFactory;
class SetOrderAttribute implements ObserverInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $quoteFactory;

    /**
     * Constructor
     *
     * @param \Psr\Log\LoggerInterface $logger
     */

    public function __construct(\Psr\Log\LoggerInterface $logger,
                               \Magento\Quote\Model\QuoteFactory $quoteFactory) {
        $this->_logger = $logger;
        $this->quoteFactory =   $quoteFactory;
    }

    public function execute(Observer $observer)
    { 
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection(); 
        $tableName = $resource->getTableName('quote_item');
        $order = $observer->getEvent()->getOrder();
        $quoteId = $order->getQuoteId();
        $quote  =   $this->quoteFactory->create()->load($quoteId);
        $payment_status = "SELECT product_sku FROM quote_item WHERE quote_id ='$quoteId'";
        $result = $connection->fetchAll($payment_status);
        $product_sku = [];
        foreach ($result as $row)
            {
             $product_sku= $row['product_sku'];
            }
        $affileatevalue =   $quote->getProductSku();        
        $order->setProductSku($product_sku);
        $order->save();
}
}