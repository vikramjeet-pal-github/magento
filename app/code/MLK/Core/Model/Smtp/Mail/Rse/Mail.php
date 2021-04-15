<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace MLK\Core\Model\Smtp\Mail\Rse;

use Mageplaza\Smtp\Mail\Rse\Mail as CoreRseMail;

use Zend\Mail\Message;

/**
 * Class Mail
 */
class Mail extends CoreRseMail
{
    
    /**
     * 
     * Overwritten to prevent e-mails with empty sender name field.
     * 
     * @param $message
     * @param $storeId
     *
     * @return mixed
     */
    public function processMessage($message, $storeId)
    {
        if (!isset($this->_returnPath[$storeId])) {
            $this->_returnPath[$storeId] = $this->smtpHelper->getSmtpConfig('return_path_email', $storeId);
        }

        if ($this->_returnPath[$storeId]) {
            if ($this->smtpHelper->versionCompare('2.2.8')) {
                $message->getHeaders()->addHeaders(["Return-Path" => $this->_returnPath[$storeId]]);
            } elseif (method_exists($message, 'setReturnPath')) {
                $message->setReturnPath($this->_returnPath[$storeId]);
            }
        }

        if (!empty($this->_fromByStore) &&
            ((is_array($message->getHeaders()) && !array_key_exists("From", $message->getHeaders())) ||
                ($message instanceof Message && !$message->getFrom()->count()))
        ) {
            $message->setFrom($this->_fromByStore['email'], $this->_fromByStore['name']);
        }

        if($this->messageHasEmptyNameField($message)){
            $message->setFrom($this->_fromByStore['email'], $this->_fromByStore['name']);
        }

        return $message;
    }

    /**
     * 
     * Check if message has empty name field on sender
     * 
     * @param $message
     *
     * @return boolean
     */
    public function messageHasEmptyNameField($message)
    {
        $hasFromByStore = !empty($this->_fromByStore);
        $fromPresentInHeadersObject = ($message instanceof Message && $message->getFrom()->count());
        $fromAddressHasName = false;
        if($hasFromByStore && $fromPresentInHeadersObject){
            $fromAddresses = $message->getFrom();
            foreach($fromAddresses as $address){
                if($address->getName()){
                    $fromAddressHasName = true;
                    break;
                }
            }
        }

        return !$fromAddressHasName;
    }

    
}
