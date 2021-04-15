<?php
namespace Vonnda\StripePayments\Helper;

use StripeIntegration\Payments\Helper\Generic as StripeGenericHelper;
use Magento\Framework\Exception\CouldNotSaveException;

class Generic extends StripeGenericHelper
{

    /**
     * Adding all this stuff to handle the quote because it seems the helper can't get the quote from session correctly.
     * I've seen it pull an empty quote, no id, like 4 items in the data array. And then with the id, but all the other data is empty.
     * I don't know what the deal is, and if someone can figure it out, that would be great, but for now, I'm adding this backdoor
     * because while this helper cant grab the quote correctly, the classes calling the helper seem to do it just fine.
     */
    protected $quote;

    public function setQuote($quote)
    {
        $this->quote = $quote;
    }

    public function getSessionQuote()
    {
        return $this->getQuote();
    }

    public function getQuote()
    {
        if (isset($this->quote)) {
            return $this->quote;
        }
        if ($this->isAdmin()) { // Admin area new order page
            return $this->getBackendSessionQuote();
        }
        return $this->checkoutSession->getQuote(); // Front end checkout
    }

    // Function overwritten to comment out stack trace
    public function dieWithError($msg, $e = null)
    {
        $this->logError($msg);
        if ($e) {
            if ($e->getMessage() != $msg) {
                $this->logError($e->getMessage());
            }
            // $this->logError($e->getTraceAsString());
        }
        if ($this->isAdmin()) {
            throw new CouldNotSaveException(__($msg));
        } else if ($this->isAjaxRequest()) {
            throw new CouldNotSaveException(__($this->cleanError($msg)), $e);
        } else if ($this->isMultiShipping()) {
            throw new \Magento\Framework\Exception\LocalizedException(__($msg), $e);
        } else {
            $this->addError($this->cleanError($msg));
        }
    }
    
}