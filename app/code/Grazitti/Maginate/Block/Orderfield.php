<?php
/**
 * Copyright © 2020 Graziiti. All rights reserved.
 */

namespace Grazitti\Maginate\Block;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Orderfield extends Field
{
    /**
     * Remove scope label
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $image = $this->getViewFileUrl("Grazitti_Maginate::images/logo.svg");
        $html = '<div class="grazitti-content">Grazitti Interactive is a global ';
        $html .= 'digital services provider leveraging cloud, mobile, and social ';
        $html .= 'media technologies to reinvent the way you do business. Since 2008, ';
        $html .= 'Grazitti has been helping companies power their business with its marketing ';
        $html .= 'automation and cloud services.';
        $html .= ' <a class="grazitti-link" href="https://www.grazitti.com" target="_blank">Click here</a>';
        $html .= ' to know more Alternatively, feel free to write to us at ';
        $html .= '<a href="mailto:info@grazitti.com">info@grazitti.com</a>.<br/>';
        $html .= '<img src='.$image.' style="width:35%;float:right;padding-top: 10px;"></div>';
        return $html;
    }
}
