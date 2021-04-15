<?php 

namespace Vonnda\Subscription\Controller\Customer;  

use Magento\Framework\App\Action\Action;

//Third Party Landing Page
class ThirdPartyLanding extends Action 
{ 

  /**
   * @var \Magento\Framework\View\Result\PageFactory
   */
  protected $resultPageFactory;

  protected $session;

  /**
   * @param \Magento\Customer\Model\Session $session
   * @param \Magento\Framework\App\Action\Context $context
   * @param \Magento\Framework\View\Result\PageFactory resultPageFactory
   */
  public function __construct(
    \Magento\Framework\App\Action\Context $context,
    \Magento\Framework\View\Result\PageFactory $resultPageFactory,
    \Magento\Customer\Model\Session $session
  ){
    $this->session = $session;
    $this->resultPageFactory = $resultPageFactory;
    parent::__construct($context); 
  }

  public function execute() 
  { 
    $this->_view->loadLayout(); 
    $this->_view->renderLayout(); 
  } 

}