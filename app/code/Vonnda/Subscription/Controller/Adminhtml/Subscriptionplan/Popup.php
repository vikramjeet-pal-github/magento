<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vonnda\Subscription\Controller\Adminhtml\Subscriptionplan;

use Magento\Backend\App\AbstractAction;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Catalog\Model\ProductFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\ResultFactory;

class Popup extends AbstractAction
{
    /**
     * Authorization level of a basic admin session
     */
	const ADMIN_RESOURCE = 'Vonnda_Subscription::manage_plans';

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $factory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Model\ProductFactory $factory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ProductFactory $factory,
        LoggerInterface $logger
    ) {
        $this->registry = $registry;
        $this->factory = $factory;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Get associated grouped products grid popup
     *
     * @return \Magento\Framework\View\Result\Layout
     */
    public function execute()
    {
        $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
        return $resultLayout;
    }
}
