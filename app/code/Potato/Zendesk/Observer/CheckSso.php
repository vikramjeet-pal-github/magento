<?php
namespace Potato\Zendesk\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Potato\Zendesk\Model\Config;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Psr\Log\LoggerInterface;

class CheckSso implements ObserverInterface
{
    /** @var CookieManagerInterface  */
    private $cookieManager;

    /** @var CustomerSession  */
    protected $customerSession;

    /** @var CookieMetadataFactory  */
    protected $cookieMetadataFactory;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param CookieManagerInterface $cookieManager
     * @param CustomerSession $customerSession
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        CookieManagerInterface $cookieManager,
        CustomerSession $customerSession,
        CookieMetadataFactory $cookieMetadataFactory,
        LoggerInterface $logger
    ) {
        $this->cookieManager = $cookieManager;
        $this->customerSession = $customerSession;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $cookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setDomain($this->customerSession->getCookieDomain())
            ->setPath($this->customerSession->getCookiePath());
        try {
            $this->cookieManager->deleteCookie(Config::SSO_COOKIE_NAME, $cookieMetadata);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }

    }
}
