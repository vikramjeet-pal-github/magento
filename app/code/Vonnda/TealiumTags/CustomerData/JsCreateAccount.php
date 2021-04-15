<?php

namespace Vonnda\TealiumTags\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Session\SessionManagerInterface as CoreSession;

class JsCreateAccount extends \Tealium\Tags\CustomerData\JsCreateAccount
{
    protected $_customerSession;

    protected $_coreSession;

    protected $request;

    protected $subscriber;

    public function __construct(
        CustomerSession $customerSession,
        CoreSession $coreSession,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Newsletter\Model\Subscriber $subscriber
    ) {
        parent::__construct($customerSession, $coreSession);

        $this->_customerSession = $customerSession;
        $this->request = $request;
        $this->subscriber = $subscriber;
    }

    public function getSectionData()
    {
        $email = $this->_coreSession->getTealiumCreateAccEmail();
        $this->_coreSession->unsTealiumCreateAccEmail();

        $type = $this->_coreSession->getTealiumCreateAccType();
        $this->_coreSession->unsTealiumCreateAccType();

        $id = $this->_coreSession->getTealiumCreateAccId();
        $this->_coreSession->unsTealiumCreateAccId();

        $result = [];

        if ($id) {
            $checkSubscriber = $this->subscriber->loadByCustomerId($id);
            $isSubscribed = $checkSubscriber->isSubscribed() ? true : false;

            $result['data']['tealium_event'] = 'user_register';
            $result['data']['customer_email'] = [(string)$email];
            $result['data']['customer_id'] = [(string)$id];
            $result['data']['account_flow'] = $this->request->getFullActionName() == "customer_section_load" ? "account" : "checkout";
            $result['data']['email_preferences'] = $isSubscribed;
            $result['data']['event_category'] = 'Account';
            $result['data']['event_action'] = 'Registration Success';
            $result['data']['customer_type'] = [(string)$type];
        }

        return $result;
    }
}
