<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Vonnda\Subscription\Api\Data\SubscriptionPaymentInterface;

class SubscriptionPayment extends AbstractModel implements IdentityInterface, SubscriptionPaymentInterface
{

	const SUBSCRIPTION_CUSTOMER_ID = 'subscription_customer_id';

	const STRIPE_CUSTOMER_ID = 'stripe_customer_id';

	const PAYMENT_ID = 'payment_id';
	
	const STATUS = 'status';

	const BILLING_ADDRESS_ID = 'billing_address_id';

	const BILLING_ADDRESS = 'billing_address';

	const PAYMENT_CODE = 'payment_code';
	
	const EXPIRATION_DATE = 'expiration_date';

	const CREATED_AT = 'created_at';
	
	const UPDATED_AT = 'updated_at';

	const CACHE_TAG = 'vonnda_subscription_payment';

	protected $_cacheTag = 'vonnda_subscription_payment';

	protected $_eventPrefix = 'vonnda_subscription_payment';

	const VALID_STATUS = "valid";
	const INVALID_STATUS = "invalid";

	protected function _construct()
	{
		$this->_init('Vonnda\Subscription\Model\ResourceModel\SubscriptionPayment');
	}

	public function getIdentities()
	{
		return [self::CACHE_TAG . '_' . $this->getId()];
	}

	public function getDefaultValues()
	{
		$values = [];

		return $values;
	}
 
	/**
     * @return int $stripeCustomerId
     */
	public function getStripeCustomerId()
	{
		return $this->_getData(self::STRIPE_CUSTOMER_ID);
	}
 
    /**
     * @param int $stripeCustomerId
     * @return $this
     */
	public function setStripeCustomerId($stripeCustomerId)
	{
		$this->setData(self::STRIPE_CUSTOMER_ID, $stripeCustomerId);
		return $this;
	}

	/**
     * @return int $paymentId
     */
	public function getPaymentId()
	{
		return $this->_getData(self::PAYMENT_ID);
	}
 
    /**
     * @param int $paymentId
     * @return $this
     */
	public function setPaymentId($paymentId)
	{
		$this->setData(self::PAYMENT_ID, $paymentId);
		return $this;
	}

	/**
     * @return int $billingAddressId
     */
	public function getBillingAddress()
	{
		return $this->_getData(self::BILLING_ADDRESS);
	}
 
    /**
     * @param int $billingAddress
     * @return $this
     */
	public function setBillingAddress($billingAddress)
	{
		$this->setData(self::BILLING_ADDRESS, $billingAddress);
		return $this;
	}

    /**
     * @return string
     */
	public function getStatus()
	{
		return $this->_getData(self::STATUS);
	}
 
    /**
     * @param string $status
     * @return $this
     */
	public function setStatus($status)
	{
		$this->setData(self::STATUS, $status);
		return $this;
	}

	/**
     * @return string
     */
	public function getPaymentCode()
	{
		return $this->_getData(self::PAYMENT_CODE);
	}
 
    /**
     * @param string $paymentCode
     * @return $this
     */
	public function setPaymentCode($paymentCode)
	{
		$this->setData(self::PAYMENT_CODE, $paymentCode);
		return $this;
	}

    /**
     * @return string
     */
	public function getExpirationDate()
	{
		return $this->_getData(self::EXPIRATION_DATE);
	}
 
    /**
     * @param string $expirationDate
     * @return $this
     */
	public function setExpirationDate($expirationDate)
	{
		$this->setData(self::EXPIRATION_DATE, $expirationDate);
		return $this;
	}

    /**
     * @return string
     */
	public function getCreatedAt()
	{
		return $this->_getData(self::CREATED_AT);
	}
 
    /**
     * @param string $createdAt
     * @return $this
     */
	public function setCreatedAt($createdAt)
	{
		$this->setData(self::CREATED_AT, $createdAt);
		return $this;
	}

    /**
     * @return string
     */
	public function getUpdatedAt()
	{
		return $this->_getData(self::UPDATED_AT);
	}
 
    /**
     * @param string $updatedAt
     * @return $this
     */
	public function setUpdatedAt($updatedAt)
	{
		$this->setData(self::UPDATED_AT, $updatedAt);
		return $this;
	}

}