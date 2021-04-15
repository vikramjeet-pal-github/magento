<?php
namespace Vonnda\Checkout\Plugin\Checkout\Model;

class CompositeConfigProvider
{

    /**
     * Because Aheadworks is dumb and apparently feels the need to make their own variable with the same data...
     * @see \Aheadworks\OneStepCheckout\Model\ConfigProvider::getConfig()
     */
    public function afterGetConfig(\Magento\Checkout\Model\CompositeConfigProvider $subject, array $result)
    {
        $result['itemImageData']['itemsData'] = $result['imageData'];
        return $result;
    }

}