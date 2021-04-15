<?php

namespace Vonnda\CheckoutSurvey\Api\Data;

interface CheckoutSurveyInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{

    const CUSTOMER_EMAIL = 'customer_email';
    const ANSWER = 'answer';
    const UPDATED_AT = 'updated_at';
    const ANSWER_DETAILS = 'answer_details';
    const ENTITY_ID = 'entity_id';
    const CUSTOMER_ID = 'customer_id';
    const CREATED_AT = 'created_at';

    /**
     * Get entity_id
     * @return string|null
     */
    public function getEntityId();

    /**
     * Set entity_id
     * @param string $entityId
     * @return \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterface
     */
    public function setEntityId($entityId);

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyExtensionInterface $extensionAttributes
    );

    /**
     * Get customer_id
     * @return string|null
     */
    public function getCustomerId();

    /**
     * Set customer_id
     * @param string $customerId
     * @return \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterface
     */
    public function setCustomerId($customerId);

    /**
     * Get customer_email
     * @return string|null
     */
    public function getCustomerEmail();

    /**
     * Set customer_email
     * @param string $customerEmail
     * @return \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterface
     */
    public function setCustomerEmail($customerEmail);

    /**
     * Get answer
     * @return string|null
     */
    public function getAnswer();

    /**
     * Set answer
     * @param string $answer
     * @return \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterface
     */
    public function setAnswer($answer);

    /**
     * Get answer_details
     * @return string|null
     */
    public function getAnswerDetails();

    /**
     * Set answer_details
     * @param string $answerDetails
     * @return \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterface
     */
    public function setAnswerDetails($answerDetails);

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     * @param string $createdAt
     * @return \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * Get updated_at
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set updated_at
     * @param string $updatedAt
     * @return \Vonnda\CheckoutSurvey\Api\Data\CheckoutSurveyInterface
     */
    public function setUpdatedAt($updatedAt);
}
