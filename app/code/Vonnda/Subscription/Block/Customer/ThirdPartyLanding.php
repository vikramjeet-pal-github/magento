<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Block\Customer;

use Vonnda\Subscription\Helper\Logger;

use Carbon\Carbon;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Model\AccountManagement;


class ThirdPartyLanding extends Template
{

    const NETSUITE_SERIAL_VERIFICATION_PATH = 'vonnda_subscriptions_general/general/netsuite_verification_url';

    /**
     * Request Object
     *
     * @var \Magento\Framework\App\RequestInterface $request
     */
    protected $request;

    /**
     * Search Criteria Builder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Vonnda Logger
     *
     * @var \Vonnda\Subscription\Helper\Logger $logger
     */
    protected $logger;

    protected $customerSession;

    /**
     * 
     * Third Party Landing Block
     * 
     * @param Context $context
     * @param RequestInterface $request
     * 
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        CustomerSession $customerSession,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Logger $logger
    ){
        $this->request = $request;
        $this->customerSession = $customerSession;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;

        parent::__construct($context);
	}

    public function getIsCustomerLoggedIn()
    {
        return $this->customerSession->isLoggedIn();
    }

    public function getCustomer()
    {
        return $this->customerSession->getCustomer();
    }

    public function getNetsuiteVerificationUrl()
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $url = $this->_scopeConfig
            ->getValue(
                self::NETSUITE_SERIAL_VERIFICATION_PATH, 
                ScopeInterface::SCOPE_STORE,
                $storeId);
        return $url ?: "";

    }

    /**
     * Get minimum password length
     *
     * @return string
     * @since 100.1.0
     */
    public function getMinimumPasswordLength()
    {
        return $this->_scopeConfig->getValue(AccountManagement::XML_PATH_MINIMUM_PASSWORD_LENGTH);
    }

    /**
     * Get number of password required character classes
     *
     * @return string
     * @since 100.1.0
     */
    public function getRequiredCharacterClassesNumber()
    {
        return $this->_scopeConfig->getValue(AccountManagement::XML_PATH_REQUIRED_CHARACTER_CLASSES_NUMBER);
    }

}