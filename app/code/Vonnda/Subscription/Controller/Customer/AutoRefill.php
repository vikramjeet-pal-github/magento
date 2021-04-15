<?php 

namespace Vonnda\Subscription\Controller\Customer;  

use Magento\Framework\App\Action\Action;
use Magento\Customer\Model\Account\Redirect;

//Auto-refills
class AutoRefill extends Action { 

  /**
   * @var \Magento\Framework\View\Result\PageFactory
   */
  protected $resultPageFactory;

  /**
   * @var \Magento\Customer\Model\Account\Redirect
   */
  protected $customerRedirect;

  /**
   * @param \Magento\Customer\Model\Session $session
   * @param \Magento\Framework\App\Action\Context $context
   * @param \Magento\Framework\View\Result\PageFactory resultPageFactory
   */
  public function __construct(
    \Magento\Framework\App\Action\Context $context,
    \Magento\Framework\View\Result\PageFactory $resultPageFactory,
    \Magento\Customer\Model\Session $session,
    Redirect $customerRedirect
  ){
    $this->resultPageFactory = $resultPageFactory;
    $this->customerRedirect = $customerRedirect;
    $this->session = $session;
    parent::__construct($context);
  }

  public function execute()
  { 
    if (!$this->session->isLoggedIn()){
      $params = $this->_request->getParams();
      $queryPath = "";
      if(isset($params['subscription'])){
        $queryPath = "?subscription=" . $params['subscription'];
      }
      $this->session->setAutoRefillRedirectRoute('subscription/customer/autorefill' . $queryPath);
      return $this->_redirect('customer/account/login');
    }
    
    $this->_view->loadLayout(); 
    $this->_view->renderLayout(); 
  } 

} 
?>