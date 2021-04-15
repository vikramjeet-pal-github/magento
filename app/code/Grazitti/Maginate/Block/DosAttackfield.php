<?php
/**
 * Copyright © 2020 Graziiti. All rights reserved.
 */

namespace Grazitti\Maginate\Block;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class DosAttackfield extends Field
{
    /**
     * Remove scope label
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $html = '<div class="grazitti-content">Configure DDoS attack functionality to prevent it from affecting ';
        $html .= 'your Magento Store. Please note that DDoS functionality will only work ';
        $html .= 'if you are using Pre-fill functionality.</div>';
        return $html;
    }
}
