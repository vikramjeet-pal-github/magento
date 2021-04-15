<?php
namespace Vonnda\Checkout\Controller\Onepage;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

class Success extends \Magento\Checkout\Controller\Onepage implements HttpGetActionInterface
{

    /**
     * Order success action
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $session = $this->getOnepage()->getCheckout();
        $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->info('Rendering success page... Server IP Address: ' . $_SERVER['SERVER_ADDR'] . ' Session ID: ' . session_id());
        if (!$this->_objectManager->get(\Magento\Checkout\Model\Session\SuccessValidator::class)->isValid()) {
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical('Failed to render success page.');
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }
        $session->clearQuote();
        //@todo: Refactor it to match CQRS
        $resultPage = $this->resultPageFactory->create();
        $this->_eventManager->dispatch(
            'checkout_onepage_controller_success_action',
            [
                'order_ids' => [$session->getLastOrderId()],
                'order' => $session->getLastRealOrder()
            ]
        );
        return $resultPage;
    }

}