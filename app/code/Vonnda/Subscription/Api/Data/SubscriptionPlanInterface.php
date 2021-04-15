<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Api\Data;

interface SubscriptionPlanInterface
{

    /**
     * @return int
     */
    public function getId();
 
    /**
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * @return string
     */
	public function getTitle();
 
    /**
     * @param string $title
     * @return $this
     */
	public function setTitle($title);
    
    /**
     * @return string
     */
	public function getShortDescription();
 
    /**
     * @param string $shortDescription
     * @return $this
     */
	public function setShortDescription($shortDescription);
    
    /**
     * @return string
     */
	public function getLongDescription();
 
    /**
     * @param string $longDescription
     * @return $this
     */
	public function setLongDescription($longDescription);

    /**
     * @return string
     */
	public function getMoreInfo();
 
    /**
     * @param string $moreInfo
     * @return $this
     */
	public function setMoreInfo($moreInfo);

    /**
     * @return string
     */
	public function getFrequencyUnits();
 
    /**
     * @param string $frequencyUnits
     * @return $this
     */
	public function setFrequencyUnits($frequencyUnits);

    /**
     * @return string
     */
	public function getFrequency();
 
    /**
     * @param string $frequency
     * @return $this
     */
	public function setFrequency($frequency);

    /**
     * @return string
     */
	public function getTriggerSku();
 
    /**
     * @param string $triggerSku
     * @return $this
     */
    public function setTriggerSku($triggerSku);
    
    /**
     * @return string
     */
	public function getSortOrder();
 
    /**
     * @param string $sortOrder
     * @return $this
     */
	public function setSortOrder($sortOrder);
    
    /**
     * @return string
     */
	public function getDuration();
 
    /**
     * @param string $duration
     * @return $this
     */
	public function setDuration($duration);

    /**
     * @return string
     */
	public function getStatus();
 
    /**
     * @param string $status
     * @return $this
     */
	public function setStatus($status);
    
    /**
     * @return boolean
     */
	public function getVisible();
 
    /**
     * @param boolean $visible
     * @return $this
     */
	public function setVisible($visible);

    /**
     * @return string
     */
	public function getCreatedAt();
 
    /**
     * @param string $createdAt
     * @return $this
     */
	public function setCreatedAt($createdAt);

    /**
     * @return string
     */
	public function getUpdatedAt();
 
    /**
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);
    
    /**
     * @return string
     */
    public function getDefaultPromoIds();
 
    /**
     * @param string $defaulPromoIds
     * @return $this
     */
    public function setDefaultPromoIds($defaultPromoIds);

    /**
     * @return int
     */
    public function getNumberOfFreeShipments();
 
    /**
     * @param int $numberOfFreeShipments
     * @return $this
     */
    public function setNumberOfFreeShipments($numberOfFreeShipments);

    /**
     * @param string $deviceSku
     * @return $this
     */
    public function setDeviceSku($deviceSku);

    /**
     * @return $string
     */
    public function getDeviceSku();

    /**
     * @param boolean $required
     * @return $this
     */
    public function setPaymentRequiredForFree($required);

    /**
     * @return boolean
     */
    public function getPaymentRequiredForFree();

      /**
     * @param boolean $identifier
     * @return $this
     */
    public function setIdentifier($identifier);

    /**
     * @return $string
     */
    public function getIdentifier();

    /**
     * @return float
     */
    public function getPlanPrice();

    /**
     * @param mixed $planPrice
     * @return boolean
     */
    public function setPlanPrice($planPrice);

}