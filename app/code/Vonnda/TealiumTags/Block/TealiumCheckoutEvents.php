<?php 
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */
namespace Vonnda\TealiumTags\Block;

use Vonnda\TealiumTags\Helper\Data as DataObjectHelper;

use Carbon\Carbon;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Framework\UrlInterface;


class TealiumCheckoutEvents extends Template
{
    /**
     * Checkout Session
     *
     * @var \Magento\Checkout\Model\Session $checkoutSession
     */
    protected $checkoutSession;

    /**
     * Customer Session
     *
     * @var \Magento\Customer\Model\Session $customerSession
     */
    protected $customerSession;

    /**
     * Data Object Helper
     *
     * @var \Vonnda\TealiumTags\Helper\Data $dataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * Store Manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * Subscriber Factory
     *
     * @var \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     */
    protected $subscriberFactory;

    /**
     * Url Interface
     *
     * @var \Magento\Framework\UrlInterface $urlInterface
     */
    protected $urlInterface;

    
    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        DataObjectHelper $dataObjectHelper,
        StoreManagerInterface $storeManager,
        SubscriberFactory $subscriberFactory,
        Context $context,
        array $data = [],
        UrlInterface $urlInterface
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->storeManager = $storeManager;
        $this->subscriberFactory = $subscriberFactory;
        $this->urlInterface = $urlInterface;

        parent::__construct($context, $data);
    }

    public function getCheckoutData()
    {
        $quote = $this->checkoutSession->getQuote();
        $customer = $this->customerSession->getCustomer();
        $subscriber = $this->subscriberFactory->create()->loadByCustomerId($customer->getId());
        $isSubscribed = $subscriber->isSubscribed() ? true : false;
        $isLoggedIn = $this->customerSession->isLoggedIn() ? true : false;
        if($this->customerSession->isLoggedIn()){
            $this->checkoutSession->setHideTealiumEmailPreferences(true);
        }

        $utag_data = [
            "customer_email" => ($customer && $customer->getEmail()) ? $customer->getEmail() : "",
            "page_type" => "checkout",
            "page_url" => $this->urlInterface->getCurrentUrl(),
            "ab_test_group" => "",
            "offer_name" => "",
            "email_preferences" => $isSubscribed,
            "session_id" => $this->customerSession->getSessionId(),
            "country_code" => $this->dataObjectHelper->getCountryFromStore(),
            "cart_id" => $quote->getId(),
            "is_logged_in" => $isLoggedIn
        ];
        
        $utag_data = $this->dataObjectHelper->addProductInfoFromQuoteItems($utag_data, $quote);
        $utag_data = $this->dataObjectHelper->addCartItemsFromQuote($utag_data, $quote);
        $utag_data = $this->dataObjectHelper->addSiteInfo($utag_data);
        $utag_data['cart_url'] = $this->getUrl('checkout/cart', ['_secure' => true]);

        return json_encode($utag_data);
    }

}