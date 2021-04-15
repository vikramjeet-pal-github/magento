<?php
namespace Vonnda\StripePayments\Model;

use Vonnda\StripePayments\Api\Data\StripeCustomerInterface;

class StripeCustomer extends \StripeIntegration\Payments\Model\StripeCustomer implements StripeCustomerInterface
{

    protected $_config;
    protected $_helper;
    protected $_customerSession;

    /**
     * Modified to set the name param instead of description
     * @param $customerFirstname
     * @param $customerLastname
     * @param $customerEmail
     * @param $customerId
     * @param array|null $params
     * @param int|null $stripeCustomerId
     * @return \Stripe\Customer|null
     * @throws \StripeIntegration\Payments\Exception\SilentException
     */
    public function createNewStripeCustomer($customerFirstname, $customerLastname, $customerEmail, $customerId, $params = null, $stripeCustomerId = null)
    {
        try {
            if (empty($params)) {
                $params = [];
            }
            $params['name'] = "$customerFirstname $customerLastname";
            $params['email'] = $customerEmail;
            $this->_stripeCustomer = \Stripe\Customer::create($params);
            $this->_stripeCustomer->save();
            if ($stripeCustomerId != null) {
                $this->setId($stripeCustomerId);
            }
            $this->setStripeId($this->_stripeCustomer->id);
            $this->setCustomerId($customerId);
            $this->setLastRetrieved(time());
            $this->setCustomerEmail($customerEmail);
            $this->updateSessionId();
            $this->save();
            return $this->_stripeCustomer;
        } catch (\Exception $e) {
            if ($this->_helper->isStripeAPIKeyError($e->getMessage())) {
                $this->_config->setIsStripeAPIKeyError(true);
                throw new \StripeIntegration\Payments\Exception\SilentException(__($e->getMessage()));
            }
            $msg = __('Could not set up customer profile: %1', $e->getMessage());
            $this->_logger->addError((string)$msg);
            $this->_helper->dieWithError($msg, $e);
        }
    }

    //The following methods were added to satisfy the service update method
    /** {@inheritdoc} */
    public function getId()
    {
        return parent::getId();
    }

    /** {@inheritdoc} */
    public function getCustomerId()
    {
        return $this->_getData('customer_id');
    }
    
    /** {@inheritdoc} */
    public function getStripeId()
    {
        return $this->_getData('stripe_id');
    }
    
    /** {@inheritdoc} */
    public function getLastRetrieved()
    {
        return $this->_getData('last_retrieved');
    }

    /** {@inheritdoc} */
    public function getCustomerEmail()
    {
        return $this->_getData('customer_email');
    }

    /** {@inheritdoc} */
    public function getSessionId()
    {
        return $this->_getData('session_id');
    }

    /** {@inheritdoc} */
    public function setStripeId($stripeId)
    {
        $this->setData('stripe_id', $stripeId);
        return $this;
    }
    
}