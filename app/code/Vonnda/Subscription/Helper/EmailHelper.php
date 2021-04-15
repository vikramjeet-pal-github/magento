<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */
namespace Vonnda\Subscription\Helper;

use Vonnda\Subscription\Helper\Logger;
use Vonnda\Subscription\Api\SubscriptionProductRepositoryInterface;


use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\Mail\Template\TransportBuilderFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\MailException;


class EmailHelper extends AbstractHelper
{
    const XML_PATH_EMAIL_CONFIG_AUTO_RENEWAL_CHARGE_ATTEMPT = 'vonnda_subscriptions_email/auto_renewal_charge_attempt';
    const XML_PATH_EMAIL_CONFIG_TURN_OFF_AUTO_RENEW = 'vonnda_subscriptions_email/turn_off_auto_renew';

    const XML_PATH_EMAIL_CONFIG_AUTO_REFILL_ACTIVATION_SUCCESS = 'vonnda_subscriptions_email/auto_refill_activation_success';
    const XML_PATH_EMAIL_CONFIG_AUTORENEW_CHARGE_FAILURE = 'vonnda_subscriptions_email/auto_renew_decline';

    const XML_PATH_EMAIL_CONFIG_REFILL_REMINDER_10_DAY = 'vonnda_subscriptions_email/refill_reminder_10_day';
    const XML_PATH_EMAIL_CONFIG_REFILL_REMINDER_30_DAY = 'vonnda_subscriptions_email/refill_reminder_30_day';

    const XML_PATH_EMAIL_CONFIG_SUBSCRIPTION_RETURNED = 'vonnda_subscriptions_email/subscription_returned';

    const DEBUG = false;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    private $inlineTranslation;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilderFactory
     */
    private $transportBuilderFactory;

    /**
     * @var \Vonnda\Subscription\Helper\Logger $logger
     */
    private $logger;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     */
    private $productRepository;

    /**
     * @var \Vonnda\Subscription\Api\SubscriptionProductRepositoryInterface $subscriptionProductRepository
     */
    private $subscriptionProductRepository;

    /**
     * EmailHelper
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        StateInterface $inlineTranslation,
        TransportBuilderFactory $transportBuilderFactory,
        Logger $logger,
        ProductRepositoryInterface $productRepository,
        SubscriptionProductRepositoryInterface $subscriptionProductRepository
    ) {
        $this->storeManager = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->transportBuilderFactory = $transportBuilderFactory;
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->subscriptionProductRepository = $subscriptionProductRepository;

        parent::__construct($context);
    }

    /**
     * Return store
     * 
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStore()
    {
        return $this->storeManager->getStore();
    }

    public function getTemplateFromPath($path, $storeId = null)
    {
        if(!$storeId){
            $storeId = $this->getStore()->getId();
        }
        return $this->scopeConfig->getValue(
            $path . "/template",
            ScopeInterface::SCOPE_STORE, 
            $storeId
        );
    }

    public function getEnabledFromPath($path, $storeId = null)
    {
        if(!$storeId){
            $storeId = $this->getStore()->getId();
        }
        return $this->scopeConfig->getValue(
            $path . "/enabled",
            ScopeInterface::SCOPE_STORE, 
            $storeId
        );
    }

    public function getSenderFromPath($path, $storeId = null)
    {
        if(!$storeId){
            $storeId = $this->getStore()->getId();
        }
        return $this->scopeConfig->getValue(
            $path . "/identity",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * 
     * Default template generation
     * 
     * @param $variable
     * @param $receiverInfo
     * @param $templateId
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function generateTemplate($templateVariables, $receiverInfo, $templateId, $sender, $storeId)
    {
        if(!$storeId){
            $storeId = $this->storeManager->getStore()->getId();
        }
        $this->transportBuilder->setTemplateIdentifier($templateId)
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $storeId,
                ]
            )
            ->setTemplateVars($templateVariables)
            ->setFrom($sender)
            ->addTo($receiverInfo['email'], $receiverInfo['name']);

        return $this;
    }

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer;
     * @param \Vonnda\Subscription\Model\SubscriptionCustomer $subscriptionCustomer
     * @param string $template
     * @param array $additonalData
     * @param string $templatingStrategy
     * @return $this
     * @throws \Magento\Framework\Exception\MailException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function sendEmail(
        string $path,
        $customer,
        array $templateVariables)
    {
        try {
            if(!$customer){
                throw new \Exception("Cannot send e-mail to null customer");
            }

            $this->inlineTranslation->suspend();
            
            $storeId = null;
            $subsciptionPlanAvailable = isset($templateVariables['subscriptionCustomer'])
                && $templateVariables['subscriptionCustomer']->getSubscriptionPlan();
            
            if($subsciptionPlanAvailable){
                $subscriptionPlan = $templateVariables['subscriptionCustomer']->getSubscriptionPlan();
                if($subscriptionPlan->getStoreId()){
                    $storeId = $subscriptionPlan->getStoreId();
                }
            }

            if(!$storeId){
                $storeId = $customer->getStoreId();
            }

            $emailIsEnabled = $this->getEnabledFromPath($path, $storeId);
            if(!$emailIsEnabled){
                return;
            }
            
            $templateId = $this->getTemplateId($path, $templateVariables, $storeId);
            $sender = $this->getSenderFromPath($path, $storeId);

            $receiverInfo = [
                'name' => $customer->getFirstname() . ' ' . $customer->getLastname(),
                'email' => $customer->getEmail()
            ];

            if(self::DEBUG){
                $this->logger->info("Sending transactional e-mail: ");
                $this->logger->info("Path - " . $path);
                $this->logger->info("Store Id - " . $storeId);
                $this->logger->info(json_encode([$templateId, $sender]));
                $this->logger->info(json_encode($receiverInfo));
            }
            
            $transportBuilder = $this->transportBuilderFactory->create();
            if(!$storeId){
                $storeId = $this->storeManager->getStore()->getId();
            }
            $transportBuilder->setTemplateIdentifier($templateId)
                ->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => $storeId,
                    ]
                )
                ->setTemplateVars($templateVariables)
                ->setFrom($sender)
                ->addTo($receiverInfo['email'], $receiverInfo['name']);

            $transport = $transportBuilder->getTransport();
            $transport->sendMessage();

            $this->inlineTranslation->resume();

            return $this;
        } catch(\Error $e){
            $this->logger->info("Failure sending subscription e-mail - a serious error occurred " . $e->getMessage());
        } catch(\Exception $e){
            $this->logger->info("Failure sending subscription e-mail " . $e->getMessage());
        } catch(MailException $e){
            $this->logger->info("Failure sending subscription e-mail " . $e->getMessage());
        }
    }

    //TODO - The subject could pull from the config too
    public function sendChargeAttemptEmail(
        $customer,
        array $templateVariables
    ){
        $templateVariables['subject'] = "Autorenewal charge attempt";
        $this->sendEmail(self::XML_PATH_EMAIL_CONFIG_AUTO_RENEWAL_CHARGE_ATTEMPT, $customer, $templateVariables);
    }

    public function sendSignupSuccessEmail(
        $customer,
        array $templateVariables
    ){
        $templateVariables['subject'] = "Successful sign up";

        $address = $templateVariables['subscriptionCustomer']->getShippingAddress();
        $street = $address ? $address->getStreet() : "";
        $streetOne = $address ? $street[0] : "";
        $streetTwo = ($address && isset($street[1])) ? $street[1] : "";

        $templateVariables['customerFirstName'] = $address ? $address->getFirstname() : "";
        $templateVariables['customerLastName'] = $address ? $address->getLastname() : "";
        $templateVariables['customerStreetOne'] = $streetOne;
        $templateVariables['customerStreetTwo'] = $streetTwo;
        $templateVariables['customerCity'] = $address ? $address->getCity() : "";
        $templateVariables['customerZip'] = $address ? $address->getPostcode() : "";
        $templateVariables['customerState'] = $address ? $address->getRegion()->getRegionCode() : "";
        $templateVariables['customerCountry'] = $address ? $address->getCountryId() : "";

        $templateVariables['subscriptionPlan'] = $templateVariables['subscriptionCustomer']->getSubscriptionPlan();
        $templateVariables['subscriptionPlanName'] = $templateVariables['subscriptionCustomer']->getSubscriptionPlan()->getShortDescription();
        $templateVariables['subscriptionRenewalDate'] = $templateVariables['subscriptionCustomer']->getRenewalDate();
        $device =  $templateVariables['subscriptionCustomer']->getDevice();
        $templateVariables['subscriptionDeviceSerial'] =$device ? $device->getSerialNumber() : "";
        $this->sendEmail(self::XML_PATH_EMAIL_CONFIG_AUTO_REFILL_ACTIVATION_SUCCESS, $customer, $templateVariables);
    }

    public function sendTurnOffAutoRenewEmail(
        $customer,
        array $templateVariables
    ){
        $templateVariables['subject'] = "Turn Off AutoRenew";
        $this->sendEmail(self::XML_PATH_EMAIL_CONFIG_TURN_OFF_AUTO_RENEW, $customer, $templateVariables);
    }

    public function sendAutoRenewChargeFailureEmail(
        $customer,
        array $templateVariables
    ){
        $templateVariables['subject'] = "Charge Failure";
        $this->sendEmail(self::XML_PATH_EMAIL_CONFIG_AUTORENEW_CHARGE_FAILURE, $customer, $templateVariables);
    }

    public function send10DayUpcomingRefillShipDateEmail(
        $customer,
        array $templateVariables
    ){
        $templateVariables['subject'] = "Filter coming up soon - 10 days";

        $address = $templateVariables['subscriptionCustomer']->getShippingAddress();
        if(isset($address)){
            $street = $address->getStreet();
            $streetOne = $street[0];
            $streetTwo = isset($street[1]) ? $street[1] : "";    
        } else {
            $streetOne = "";
            $streetTwo = "";
        }
        $templateVariables['customerFirstName'] = isset($address) ? $address->getFirstname() : "";
        $templateVariables['customerLastName'] = isset($address) ? $address->getLastname() : "";
        $templateVariables['customerStreetOne'] = $streetOne;
        $templateVariables['customerStreetTwo'] = $streetTwo;
        $templateVariables['customerCity'] = isset($address) ? $address->getCity() : "";
        $templateVariables['customerZip'] = isset($address) ? $address->getPostcode() : "";
        $templateVariables['customerState'] = isset($address) ? $address->getRegion()->getRegionCode() : "";
        $templateVariables['customerCountry'] = isset($address) ? $address->getCountryId() : "";
        $this->sendEmail(self::XML_PATH_EMAIL_CONFIG_REFILL_REMINDER_10_DAY, $customer, $templateVariables);
    }

    public function send30DayUpcomingRefillShipDateEmail(
        $customer,
        array $templateVariables
    ){
        $templateVariables['subject'] = "Filter coming up soon - 30 days";
        $subscriptionPlan = $templateVariables['subscriptionCustomer']->getSubscriptionPlan();

        if(!$this->shouldSend30DayEmail($templateVariables['subscriptionCustomer'], $subscriptionPlan)){
            return;
        }

        $templateVariables['subscriptionPlan'] = $subscriptionPlan;
        $templateVariables['subscriptionPlanName'] = $templateVariables['subscriptionCustomer']->getSubscriptionPlan()->getTitle();
        $templateVariables['subscriptionRenewalDate'] = $templateVariables['subscriptionCustomer']->getRenewalDate();
        
        $device =  $templateVariables['subscriptionCustomer']->getDevice();
        $templateVariables['subscriptionDeviceSerial'] =$device ? $device->getSerialNumber() : "";
        $this->sendEmail(self::XML_PATH_EMAIL_CONFIG_REFILL_REMINDER_30_DAY, $customer, $templateVariables);
    }

    public function shouldSend30DayEmail(
        $subscriptionCustomer, 
        $subscriptionPlan
    ){
        if($subscriptionPlan->getIdentifier() === 'mh1-sub-legacy-6500-payment-required' 
            && $subscriptionCustomer->hasFreeShipmentsLeft()){
            return false;
        }

        return true;
    }

    public function sendSubscriptionReturnedEmail(
        $customer,
        array $templateVariables
    ){
        $templateVariables['subject'] = "Device Returned";
        $templateVariables['customer'] = $customer;

        $this->sendEmail(self::XML_PATH_EMAIL_CONFIG_SUBSCRIPTION_RETURNED, $customer, $templateVariables);
    }

    public function hasFreeShipment($templateVariables)
    {
        if(isset($templateVariables['subscriptionCustomer']) && $templateVariables['subscriptionCustomer']){
            return $templateVariables['subscriptionCustomer']->hasFreeShipmentsLeft();
        }
        return false;
    }

    public function getTemplateId($path, $templateVariables, $storeId)
    {
        if($path === self::XML_PATH_EMAIL_CONFIG_AUTORENEW_CHARGE_FAILURE){
            $templateId = $this->scopeConfig->getValue(
                    $path . "/attempt_" . $templateVariables['attemptNumber'] . "_template",
                    ScopeInterface::SCOPE_STORE,
                    $storeId
                );
        } elseif($path === self::XML_PATH_EMAIL_CONFIG_AUTO_REFILL_ACTIVATION_SUCCESS) {
            $hasFreeShipment = $this->hasFreeShipment($templateVariables);
            if($hasFreeShipment){
                $templateId = $this->scopeConfig->getValue(
                    $path . "/template_free",
                    ScopeInterface::SCOPE_STORE,
                    $storeId
                );
            } else {
                $templateId = $this->getTemplateFromPath($path, $storeId);
            }
        } else {
            $templateId = $this->getTemplateFromPath($path, $storeId);
        }

        return $templateId;
    }

}