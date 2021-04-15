<?php
namespace Grazitti\Warranty\Controller\Index;
use Magento\Framework\App\Action\Context;
 
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;

class Addtocart extends \Magento\Framework\App\Action\Action

{
    protected $_resultPageFactory;
    protected $_cart;
    protected $_productRepositoryInterface;
    protected $_url;
    protected $_responseFactory;
    protected $_logger;
 
 
    public function __construct(
        Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Sales\Model\Order $order,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Psr\Log\LoggerInterface $logger
        )
    {
        $this->_resultPageFactory = $resultPageFactory;
        $this->_cart = $cart;
        $this->order = $order;
        $this->_productRepositoryInterface = $productRepositoryInterface;
        $this->_responseFactory = $responseFactory;
        $this->_url = $context->getUrl();
        $this->_logger = $logger;
        parent::__construct($context);
    }
 
    public function execute()
    {
    

    $order = $this->order;
    $order->setState(\Magento\Sales\Model\Order::BASE_TAX_AMOUNT, true);
    $order->setStatus(\Magento\Sales\Model\Order::BASE_TAX_AMOUNT);
    //$order->addStatusToHistory($order->getStatus(), 'Order processed successfully with reference');
    $order->save();
}

}