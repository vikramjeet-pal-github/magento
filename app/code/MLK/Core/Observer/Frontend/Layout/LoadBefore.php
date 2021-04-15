<?php
namespace MLK\Core\Observer\Frontend\Layout;

use Magento\Framework\Event\Observer;
use Magento\Framework\View\Page\Config;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class LoadBefore
 */
class LoadBefore implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * @var Config
     */
    protected $config;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * LoadBefore constructor.
     *
     * @param Config                $config
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Config $config,
        StoreManagerInterface $storeManager
    ) {
        $this->config = $config;
        $this->storeManager = $storeManager;
    }

    /**
     * @param Observer $observer
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        $store = $this->storeManager->getStore();
        $websiteCode = $store->getWebsite()->getCode();
        $this->config->addBodyClass($websiteCode);
    }
}