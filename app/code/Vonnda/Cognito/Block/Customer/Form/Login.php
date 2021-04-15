<?php
namespace Vonnda\Cognito\Block\Customer\Form;

class Login extends \Magento\Customer\Block\Form\Login
{

    public function mustValidate()
    {
        if ($this->_customerSession->getRequireCognitoValidation() === 1) {
            return true;
        }
        return false;
    }

}