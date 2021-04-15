<?php

namespace DEG\CustomReports\Block\Adminhtml\CustomReport\Edit;

class GenericButton
{
    /**
     * GenericButton constructor.
     * @param \Magento\Backend\Block\Widget\Context $context
     */
    public function __construct(\Magento\Backend\Block\Widget\Context $context)
    {
        $this->context = $context;
    }

    public function getBackUrl()
    {
        return $this->getUrl('*/*/listing');
    }

    public function getDeleteUrl()
    {
        return $this->getUrl('*/*/delete', ['object_id' => $this->getObjectId()]);
    }

    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }

    public function getObjectId()
    {
        return $this->context->getRequest()->getParam('customreport_id');
    }
}
