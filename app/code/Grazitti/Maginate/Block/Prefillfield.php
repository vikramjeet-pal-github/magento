<?php
/**
 * Copyright © 2020 Graziiti. All rights reserved.
 */

namespace Grazitti\Maginate\Block;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Prefillfield extends Field
{
    /**
     * Remove scope label
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $html='<div class="grazitti-content">Enable Prefill for Marketo forms embedded on the Magento store</div>';
        return $html;
    }
}
