<?php
namespace Potato\Zendesk\Plugin;

use Potato\Zendesk\Api\SsoManagementInterface;
use Potato\Zendesk\Model\Config;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\Result\Redirect;

class Logout
{
    /** @var Config */
    protected $config;

    /** @var SsoManagementInterface */
    protected $ssoManagement;

    /** @var RedirectFactory */
    protected $resultRedirectFactory;

    /**
     * @param Config $config
     * @param SsoManagementInterface $ssoManagement
     * @param RedirectFactory $resultRedirectFactory
     */
    public function __construct(
        Config $config,
        SsoManagementInterface $ssoManagement,
        RedirectFactory $resultRedirectFactory
    ) {
        $this->config = $config;
        $this->ssoManagement = $ssoManagement;
        $this->resultRedirectFactory = $resultRedirectFactory;
    }

    /**
     * @param \Magento\Customer\Controller\Account\Logout $subject
     * @param \Closure $proceed
     *
     * @return Redirect
     */
    public function aroundExecute(
        \Magento\Customer\Controller\Account\Logout $subject,
        \Closure $proceed
    ) {
        if (!$this->config->isSsoEnabled()) {
            return $proceed();
        }
        $customerId = $subject->getRequest()->getParam('external_id', null);
        if (null === $customerId) {
            return $proceed();
        }
        $url = $this->ssoManagement->getLogoutUrl($customerId);
        if (null === $url) {
            return $proceed();
        }
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($url);
        return $resultRedirect;
    }
}