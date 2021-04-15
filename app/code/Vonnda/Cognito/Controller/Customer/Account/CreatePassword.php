<?php
namespace Vonnda\Cognito\Controller\Customer\Account;

class CreatePassword extends \Magento\Customer\Controller\Account\CreatePassword
{

    /** @inheritDoc
     * Override created to remove token validation, since it will be the cognito token, and not magento
     * also commenting out the if that just redirects back to the page after setting the token in session
     * and adding the email to the block as well, since core reset just uses the token saved to the customer
     * and finally, adding checks for token and email in the try, since the core token validation has been removed
     */
    public function execute()
    {
        $resetPasswordToken = (string)$this->getRequest()->getParam('token');
        $resetPasswordEmail = (string)$this->getRequest()->getParam('email');
        
		if($this->getRequest()->getParam('lang') == 'jp'){
			$resultPage = $this->resultPageFactory->create();
			$resultPage->getConfig()->getTitle()->set('パスワードをリセット');
			$resultPage->getLayout()
			->getBlock('resetPassword')
			->setTemplate('Magento_Customer::form/resetforgottenpasswordjp.phtml')
			->setResetPasswordLinkToken($resetPasswordToken)
			->setResetPasswordLinkEmail($resetPasswordEmail);
			return $resultPage;
		}
		
		if($this->getRequest()->getParam('lang') == 'kr'){
			$resultPage = $this->resultPageFactory->create();
			$resultPage->getConfig()->getTitle()->set('암호 재설정');
			$resultPage->getLayout()
			->getBlock('resetPassword')
			->setTemplate('Magento_Customer::form/resetforgottenpasswordkr.phtml')
			->setResetPasswordLinkToken($resetPasswordToken)
			->setResetPasswordLinkEmail($resetPasswordEmail);
			return $resultPage;
		}
		
		/*
        $isDirectLink = $resetPasswordToken != '';
        if (!$isDirectLink) {
            $resetPasswordToken = (string)$this->session->getRpToken();
        }
        */
        try {
            if (!$resetPasswordToken || !$resetPasswordEmail) {
                throw new \Exception();
            }
            /*
            $this->accountManagement->validateResetPasswordLinkToken(null, $resetPasswordToken);
            if ($isDirectLink) {
                $this->session->setRpToken($resetPasswordToken);
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setPath('* /* /createpassword'); // added spaces to make block comment work
                return $resultRedirect;
            } else {
             */
            /** @var \Magento\Framework\View\Result\Page $resultPage */
            $resultPage = $this->resultPageFactory->create();
            $resultPage->getLayout()
                ->getBlock('resetPassword')
                ->setResetPasswordLinkToken($resetPasswordToken)
                ->setResetPasswordLinkEmail($resetPasswordEmail);
            return $resultPage;
            // }
        } catch (\Exception $exception) {
            $this->messageManager->addError(__('Your password reset link has expired.'));
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*/forgotpassword');
            return $resultRedirect;
        }
    }

}