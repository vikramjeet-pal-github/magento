<?php 

namespace MLK\Core\Controller\Customer;  

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Customer\Model\Session;



class Referrals extends Action { 

  const PATH = "/mlk_core/customer/referrals";
  
  /**
   * @var \Magento\Framework\View\Result\PageFactory
   */
  protected $resultPageFactory;

  /**
   * @param \Magento\Customer\Model\Session $session
   * @param \Magento\Framework\App\Action\Context $context
   * @param \Magento\Framework\View\Result\PageFactory resultPageFactory
   */
  public function __construct(
    Context $context,
    PageFactory $resultPageFactory,
    Session $session
  ){
    $this->resultPageFactory = $resultPageFactory;
    $this->session = $session;
    parent::__construct($context);

    if (!$session->isLoggedIn()){
      $this->_redirect('customer/account/login');
    }
  }

  public function execute()
  { 
    $this->_view->loadLayout(); 
    $this->_view->renderLayout(); 
  } 

} 
