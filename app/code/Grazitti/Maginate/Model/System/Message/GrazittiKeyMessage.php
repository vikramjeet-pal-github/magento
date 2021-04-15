<?php
/**
 * Copyright © 2020 Grazitti. All rights reserved.
 */
namespace Grazitti\Maginate\Model\System\Message;

use Magento\Framework\Notification\MessageInterface;

class GrazittiKeyMessage implements MessageInterface
{

    protected $_objectManager;
    protected $urlInterface;
    public $scopeConfig;
    protected $date;
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\DateTime\DateTime $date
    ) {
        $this->_objectManager = $objectmanager;
        $this->urlInterface = $urlInterface;
        $this->scopeConfig = $scopeConfig;
        $this->date = $date;
    }

   /**
    * @var MESSAGE IDENTITY
    */
    const MESSAGE_IDENTITY = 'maginate_system_message';
   /**
    * Retrieve unique system message identity
    *
    * @return string
    */
    public function getIdentity()
    {
        return self::MESSAGE_IDENTITY;
    }
   /**
    * Check whether the system message should be shown
    *
    * @return bool
    */
    public function isDisplayed()
    {
        // The message will be shown
        $expiry = $this->scopeConfig->getValue('grazz_auth/graz_settings/expiry_date');
        $curdate = strtotime($this->date->gmtDate('Y-m-d'));
        $exdate  = strtotime($this->date->gmtDate('Y-m-d', $expiry));
        if ($expiry !='failure' && $expiry !='invalid') {
            if ($curdate >= $exdate) {
                return true;
            } else {
                return false;
            }
        }
    }
   /**
    * Retrieve system message text
    *
    * @return \Magento\Framework\Phrase
    */
    public function getText()
    {
        $message = 'Licence of your Marketo integration <a href="';
        $message .= $this->urlInterface->getUrl('adminhtml/system_config/edit/section/grazz_auth');
        $message .= '">connector</a> is expired. Please get in touch with ';
        $message .= '<a href="mailto:support@grazitti.com">support@grazitti.com</a>';
        $message .= ' to get it renewed.';
        return __($message);
    }
   /**
    * Retrieve system message severity
    * Possible default system message types:
    * - MessageInterface::SEVERITY_CRITICAL
    * - MessageInterface::SEVERITY_MAJOR
    * - MessageInterface::SEVERITY_MINOR
    * - MessageInterface::SEVERITY_NOTICE
    *
    * @return int
    */
    public function getSeverity()
    {
        return self::SEVERITY_MAJOR;
    }
}
