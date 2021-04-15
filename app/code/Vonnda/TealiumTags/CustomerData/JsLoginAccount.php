<?php

namespace Vonnda\TealiumTags\CustomerData;

use Vonnda\TealiumTags\Helper\Data as DataObjectHelper;

use Tealium\Tags\CustomerData\JsLoginAccount as CoreJsLoginAccount;

use Magento\Framework\Session\SessionManagerInterface as CoreSession;
use Magento\Framework\App\Request\Http;


class JsLoginAccount extends CoreJsLoginAccount
{
    protected $customerSession;

    protected $request;

    protected $dataObjectHelper;

    public function __construct(
        CoreSession $coreSession,
        Http $request,
        DataObjectHelper $dataObjectHelper
    ) {
        $this->request = $request;
        $this->dataObjectHelper = $dataObjectHelper;
        
        parent::__construct($coreSession);
    }

    public function getSectionData()
    {
        $email = $this->_coreSession->getTealiumLoginEmail();
        $this->_coreSession->unsTealiumLoginEmail();

        $type = $this->_coreSession->getTealiumLoginType();
        $this->_coreSession->unsTealiumLoginType();

        $id = $this->_coreSession->getTealiumLoginId();
        $this->_coreSession->unsTealiumLoginId();

        $result = [];

        if ($id) {
            $result['data']['tealium_event'] = "user_login";
            $result['data']['customer_email'] = $email;
            $result['data']['customer_id'] = $id;
            $result['data']['login_flow'] = $this->request->getFullActionName() == "customer_section_load" ? "account" : "checkout";
            $result['data']['customer_type'] = $type;
            $result['data']['event_category'] = "Account";
            $result['data']['event_action'] = "Login Success";
            $result['data']['account_flow'] = "Account";
            $result['data'] = array_merge(
                $result['data'], 
                $this->dataObjectHelper->getCartInfo(), 
                $this->dataObjectHelper->getSubscriptionInfoForLogin($id));
        }

        return $result;
    }
}
