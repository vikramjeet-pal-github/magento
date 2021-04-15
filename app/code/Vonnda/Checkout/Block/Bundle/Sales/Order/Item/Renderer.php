<?php
namespace Vonnda\Checkout\Block\Bundle\Sales\Order\Item;

class Renderer extends \Magento\Bundle\Block\Sales\Order\Items\Renderer
{

    protected $deviceHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory
     * @param \Vonnda\Subscription\Helper\DeviceHelper $deviceHelper
     * @param array $data
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory,
        \Vonnda\Subscription\Helper\DeviceHelper $deviceHelper,
        array $data = [],
        \Magento\Framework\Serialize\Serializer\Json $serializer = null
    ) {
        parent::__construct($context, $string, $productOptionFactory, $data, $serializer);
        $this->deviceHelper = $deviceHelper;
    }

    public function isSubActive()
    {
        return $this->deviceHelper->isSubActive($this->getOrder());
    }

}