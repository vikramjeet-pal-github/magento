<?php

namespace Grazitti\Warranty\Block\Adminhtml\Order\View\Tab;

class CustomTab extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
   protected $_template = 'order/view/ordershipping.phtml';
   /**
    * @var \Magento\Framework\Registry
    */
   private $orderItem;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @param array $data
     */
    public function __construct(
       \Magento\Backend\Block\Template\Context $context,
       \Magento\Framework\Registry $registry,
       array $data = []
   ) {
       $this->_coreRegistry = $registry;
       parent::__construct($context, $data);
   }

   /**
    * Retrieve order model instance
    * 
    * @return \Magento\Sales\Model\Order
    */
   public function getOrder()
   {
       return $this->_coreRegistry->registry('current_order');
   }
   /**
    * Retrieve order model instance
    *
    * @return int
    *Get current id order
    */
   public function getOrderId()
   {
       return $this->getOrder()->getEntityId();
   }
   /**
    * Retrieve order increment id
    *
    * @return string
    */
   public function getOrderIncrementId()
   {
       return $this->getOrder()->getIncrementId();
   }
   /**
    * {@inheritdoc}
    */
   public function getTabLabel()
   {
       return __('My Custom Tab');
   }

   /**
    * {@inheritdoc}
    */
   public function getTabTitle()
   {
       return __('My Custom Tab');
   }

   /**
    * {@inheritdoc}
    */
   public function canShowTab()
   {
       return true;
   }

   /**
    * {@inheritdoc}
    */
   public function isHidden()
   {
       return false;
   }
}
   