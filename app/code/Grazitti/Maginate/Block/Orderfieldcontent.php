<?php
/**
 * Copyright © 2020 Graziiti. All rights reserved.
 */

namespace Grazitti\Maginate\Block;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Orderfieldcontent extends Field
{
    /**
     * Remove scope label
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        
        $html='<div class="grazitti-content">This will work for Guest checkout.</div>';
        return $html;
    }
}
