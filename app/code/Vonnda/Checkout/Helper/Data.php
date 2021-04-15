<?php
namespace Vonnda\Checkout\Helper;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $productRepository;
    protected $httpContext;
    protected $session;
    protected $priceHelper;
    protected $checkout;
    protected $attributeSetCollection;
    protected $accountManagement;
    protected $storeManager;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Customer\Model\Session $session
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Magento\Checkout\Model\Session $checkout
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attributeSetCollection
     * @param \Magento\Customer\Model\AccountManagement $accountManagement
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Customer\Model\Session $session,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Checkout\Model\Session $checkout,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attributeSetCollection,
        \Magento\Customer\Model\AccountManagement $accountManagement,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->productRepository = $productRepository;
        $this->httpContext = $httpContext;
        $this->session = $session;
        $this->priceHelper = $priceHelper;
        $this->checkout = $checkout;
        $this->attributeSetCollection = $attributeSetCollection;
        $this->accountManagement = $accountManagement;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * Get Any Store Configuration
     * @param string $storePath Full path of any configuration
     * @return string $storeConfig
     */
    public function getStoreConfig($storePath)
    {
        $storeConfig = $this->scopeConfig->getValue($storePath, ScopeInterface::SCOPE_STORE);
        return $storeConfig;
    }

    /**
     * Load product from productId
     * @param int $id Product id
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductById($id)
    {
        return $this->productRepository->getById($id);
    }

    /**
     * Check Customer is login or not
     * @return boolean
     */
    public function isLoggedIn()
    {
        $isLoggedIn = $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
        return $isLoggedIn;
    }

    /**
     * Get Formated Price
     * @param float|string price
     * @return boolean
     */
    public function getFormatedPrice($price = '')
    {
        return $this->priceHelper->currency($price, true, false);
    }

    public function isDeviceInCart()
    {
        $deviceSetId = $this->attributeSetCollection->create()->addFieldToSelect('*')->addFieldToFilter('attribute_set_name', 'Device')->getFirstItem()->getAttributeSetId();
        $items = $this->checkout->getQuote()->getAllItems();
        foreach ($items as $item) {
            if ($item->getProduct()->getAttributeSetId() == $deviceSetId) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $email
     * @return bool|\Magento\Customer\Api\Data\CustomerInterface
     * @throws NoSuchEntityException
     */
    public function getCustomerIfExists($email)
    {
        return $this->accountManagement->getCustomerIfExists($email, $this->storeManager->getStore()->getWebsiteId());
    }

}