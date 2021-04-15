<?php
namespace Vonnda\Checkout\Block\Cart\Item;

class BundleRenderer extends \Magento\Bundle\Block\Checkout\Cart\Item\Renderer
{

    protected $stockRegistryProvider;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Catalog\Helper\Product\Configuration $productConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Catalog\Block\Product\ImageBuilder|\Magento\Catalog\Helper\Image $imageBuilder
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\View\Element\Message\InterpretationStrategyInterface $messageInterpretationStrategy
     * @param \Magento\Bundle\Helper\Catalog\Product\Configuration $bundleProductConfiguration
     * @param \Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface $stockRegistryProvider
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Helper\Product\Configuration $productConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\View\Element\Message\InterpretationStrategyInterface $messageInterpretationStrategy,
        \Magento\Bundle\Helper\Catalog\Product\Configuration $bundleProductConfiguration,
        \Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface $stockRegistryProvider,
        array $data = []
    ) {
        parent::__construct($context, $productConfig, $checkoutSession, $imageBuilder, $urlHelper, $messageManager, $priceCurrency, $moduleManager, $messageInterpretationStrategy, $bundleProductConfiguration, $data);
        $this->stockRegistryProvider = $stockRegistryProvider;
    }

    public function getStockItem($productId, $scopeId)
    {
        return $this->stockRegistryProvider->getStockItem($productId, $scopeId);
    }

}