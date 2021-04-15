<?php
/**
 * @copyright: Copyright © 2020 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */
namespace Vonnda\GiftOrder\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\Mail\Template\TransportBuilderFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Exception\MailException;
use Psr\Log\LoggerInterface;
use Magento\GiftMessage\Helper\Message as GiftMessageHelper;


class EmailHelper extends AbstractHelper
{
    const XML_PATH_EMAIL_CONFIG_GIFT_SHIPMENT = 'sales_email/gift_shipment';

    const DEBUG = true;

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
     * @var LoggerInterface $logger
     */
    private $logger;

    protected $giftMessageHelper;

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
        LoggerInterface $logger,
        GiftMessageHelper $giftMessageHelper
    ) {
        $this->storeManager = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->transportBuilderFactory = $transportBuilderFactory;
        $this->logger = $logger;
        $this->giftMessageHelper = $giftMessageHelper;

        parent::__construct($context);
    }

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

    public function sendGiftShipmentEmail($order, $shipment, $shipmentItems)
    {
        $path = self::XML_PATH_EMAIL_CONFIG_GIFT_SHIPMENT;
        
        try {
            $shippingAddress = $order->getShippingAddress();
            $giftRecipientEmail = $shippingAddress->getGiftRecipientEmail();
            $canSendEmail = $order && $order->getGiftOrder() && $giftRecipientEmail;
            if(!$canSendEmail){
                throw new \Exception("Cannot send gift receipt e-mail for null order, non gift order or null recipient e-mail");
            }

            $templateVariables = $this->getTemplateVariablesForGiftShipment($order, $shipment, $shipmentItems);

            $this->inlineTranslation->suspend();
            $storeId = $order->getStoreId();

            $emailIsEnabled = $this->getEnabledFromPath($path, $storeId);
            if(!$emailIsEnabled){
                return;
            }
            
            $templateId = $this->getTemplateFromPath($path, $storeId);
            $sender = $this->getSenderFromPath($path, $storeId);

            $receiverInfo = [
                'name' => $shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname(),
                'email' => $giftRecipientEmail
            ];

            if(self::DEBUG){
                $this->logger->info("Sending gift receipt e-mail: ");
                $this->logger->info("Path - " . $path);
                $this->logger->info("Store Id - " . $storeId);
                $this->logger->info(json_encode([$templateId, $sender]));
                $this->logger->info(json_encode($receiverInfo));
            }
            
            $transportBuilder = $this->transportBuilderFactory->create();
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
            $this->logger->info("Failure sending gift receipt e-mail - a serious error occurred " . $e->getMessage());
        } catch(\Exception $e){
            $this->logger->info("Failure sending gift receipt e-mail " . $e->getMessage());
        } catch(MailException $e){
            $this->logger->info("Failure sending gift receipt e-mail " . $e->getMessage());
        }
    }

    protected function getTemplateVariablesForGiftShipment($order, $shipment, $shipmentItems)
    {
        $templateVariables = [
            'subject' => "Gift shipment",
            'shipmentItems' => $shipmentItems,
            'order' => $order,
            'shipment' => $shipment
        ];

        $tracks = $shipment->getTracks();
        if ($tracks) {
            foreach($tracks as $track){
                $templateVariables['track'] = $track;
                break;
            }
        }

        $templateVariables['registerUrl'] = $this->storeManager->getStore()->getBaseUrl() . "yay";

        $address = $order->getShippingAddress();
        $street = $address ? $address->getStreet() : "";
        $streetOne = $address ? $street[0] : "";
        $streetTwo = ($address && isset($street[1])) ? $street[1] : "";

        $templateVariables['customerFirstName'] = $address ? $address->getFirstname() : "";
        $templateVariables['customerLastName'] = $address ? $address->getLastname() : "";
        $templateVariables['customerStreetOne'] = $streetOne;
        $templateVariables['customerStreetTwo'] = $streetTwo;
        $templateVariables['customerCity'] = $address ? $address->getCity() : "";
        $templateVariables['customerZip'] = $address ? $address->getPostcode() : "";
        $templateVariables['customerState'] = $address ? $address->getRegion() : "";
        $templateVariables['customerCountry'] = $address ? $address->getCountryId() : "";
        $templateVariables['customerTelephone'] = $address ? $address->getTelephone() : "";

        $templateVariables['shippingMethod'] = $order->getShortShippingDescription();

        $giftMessage = "";
        if($order->getGiftMessageId()) {
            $giftMessage = $this->giftMessageHelper->getGiftMessage($order->getGiftMessageId())->getMessage();
        }

        $templateVariables['giftMessage'] = $giftMessage;

        $billingAddress = $order->getBillingAddress();
        $templateVariables['senderName'] = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();
        

        $tracks = $shipment->getTracks();
        $trackingNumber = "";
        foreach($tracks as $track){
            $trackingNumber = $track->getTrackNumber();
            break;
        }
        $templateVariables['trackingNumber'] = $trackingNumber;
        return $templateVariables;
    }

}