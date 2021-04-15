<?php
/**
 * Copyright © 2020 Grazitti. All rights reserved.
 */
namespace Grazitti\Maginate\Block\Adminhtml\System\Config\Form\Field;

class Link extends \Magento\Config\Block\System\Config\Form\Field
{
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return sprintf(
            '<a href ="%s">%s</a>',
            rtrim($this->_urlBuilder->getUrl('grazitti_maginate/logs/index'), '/'),
            __('Click here to see the logs')
        );
    }
}
