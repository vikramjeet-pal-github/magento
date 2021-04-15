<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vonnda\GiftOrder\Block\Sales\Adminhtml\Order\View;

use Magento\Sales\Block\Adminhtml\Order\View\Info as CoreInfo;
use Magento\Backend\Model\UrlInterface;

/**
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Info extends CoreInfo
{
    protected $backendUrlInterface;
    
    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $adminHelper
     * @param \Magento\Customer\Api\GroupRepositoryInterface $groupRepository
     * @param \Magento\Customer\Api\CustomerMetadataInterface $metadata
     * @param \Magento\Customer\Model\Metadata\ElementFactory $elementFactory
     * @param \Magento\Sales\Model\Order\Address\Renderer $addressRenderer
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Magento\Customer\Api\CustomerMetadataInterface $metadata,
        \Magento\Customer\Model\Metadata\ElementFactory $elementFactory,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        UrlInterface $backendUrlInterface,
        array $data = []
    ) {
        $this->backendUrlInterface = $backendUrlInterface;
        
        parent::__construct(
            $context, 
            $registry, 
            $adminHelper, 
            $groupRepository, 
            $metadata,
            $elementFactory,
            $addressRenderer,
            $data);
    }

    public function getResendRecipientEmailUrl()
    {
        return $this->backendUrlInterface->getUrl("vonnda_giftorder/order/resendshipmentemail");
    }

}
