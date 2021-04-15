<?php

namespace Vonnda\CheckoutSurvey\Model\Data;

use Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterface;

class CheckoutSurvey extends \Magento\Framework\Api\AbstractExtensibleObject implements CheckoutSurveyInterface
{

    /**
     * Get entity_id
     * @return string|null
     */
    public function getEntityId()
    {
        return $this->_get(self::ENTITY_ID);
    }

    /**
     * Set entity_id
     * @param string $entityId
     * @return \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterface
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     * @param \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }

    /**
     * Get customer_id
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->_get(self::CUSTOMER_ID);
    }

    /**
     * Set customer_id
     * @param string $customerId
     * @return \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterface
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * Get customer_email
     * @return string|null
     */
    public function getCustomerEmail()
    {
        return $this->_get(self::CUSTOMER_EMAIL);
    }

    /**
     * Set customer_email
     * @param string $customerEmail
     * @return \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterface
     */
    public function setCustomerEmail($customerEmail)
    {
        return $this->setData(self::CUSTOMER_EMAIL, $customerEmail);
    }

    /**
     * Get answer
     * @return string|null
     */
    public function getAnswer()
    {
        return $this->_get(self::ANSWER);
    }

    /**
     * Set answer
     * @param string $answer
     * @return \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterface
     */
    public function setAnswer($answer)
    {
        return $this->setData(self::ANSWER, $answer);
    }

    /**
     * Get answer_details
     * @return string|null
     */
    public function getAnswerDetails()
    {
        return $this->_get(self::ANSWER_DETAILS);
    }

    /**
     * Set answer_details
     * @param string $answerDetails
     * @return \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterface
     */
    public function setAnswerDetails($answerDetails)
    {
        return $this->setData(self::ANSWER_DETAILS, $answerDetails);
    }

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->_get(self::CREATED_AT);
    }

    /**
     * Set created_at
     * @param string $createdAt
     * @return \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterface
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Get updated_at
     * @return string|null
     */
    public function getUpdatedAt()
    {
        return $this->_get(self::UPDATED_AT);
    }

    /**
     * Set updated_at
     * @param string $updatedAt
     * @return \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterface
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
