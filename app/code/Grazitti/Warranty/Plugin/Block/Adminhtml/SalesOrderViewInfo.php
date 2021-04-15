<?php
 
namespace Grazitti\Warranty\Plugin\Block\Adminhtml;
class SalesOrderViewInfo
{
    public function afterToHtml(
        \Magento\Sales\Block\Adminhtml\Order\View\Info $subject,$result){
   

        /*same as layout block name */
 
        $customBlock = $subject->getLayout()->getBlock('custom_block');
        if ($customBlock !== false && $subject->getNameInLayout() == 'order_info') {
            $result = $result . $customBlock->toHtml();
        }
 
        return $result;
 
    }
 
}