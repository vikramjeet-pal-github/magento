<?php

namespace Vonnda\StripePayments\Api\Data;

interface StripeCustomerInterface
{
     /**
     * Stripe Id
     *
     * @api
     * @param
     * @return int
     */
    public function getId();

    /**
     * Customer Id
     *
     * @api
     * @param void
     * @return int
     */
    public function getCustomerId();
    
    /**
     * Stripe Id
     *
     * @api
     * @param void
     * @return string
     */
    public function getStripeId();

    /**
     * Set Stripe Id
     *
     * @api
     * @param string $stripeId
     * @return void
     */
    public function setStripeId($stripeId);
    
    /**
     * Last Retrieved
     *
     * @api
     * @param void
     * @return string
     */
    public function getLastRetrieved();

    /**
     * Get Customer E-mail
     *
     * @api
     * @param void
     * @return string
     */
    public function getCustomerEmail();

    /**
     * Get Session Id
     *
     * @api
     * @param void
     * @return string
     */
    public function getSessionId();

}