<?php
/**
 * Copyright © 2020 Grazitti. All rights reserved.
 */

namespace Grazitti\Maginate\Block;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Productfield extends Field
{
    /**
     * Remove scope label
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $html='<div class="grazitti-content">Please create two custom objects';
        $html .= ' into marketo before enabling this feature. Please click ';
        $html .= '<a class="grazitti-link" target="_blank" ';
        $html .= 'href="http://docs.marketo.com/display/public/DOCS/Create+Marketo+Custom+Objects">here</a>';
        $html .= ' for instructions. </div>';
        return $html;
    }
}
