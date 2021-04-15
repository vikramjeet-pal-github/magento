<?php

namespace Vonnda\OrderTag\Block\Adminhtml\Order\Create;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Vonnda\OrderTag\Model\ResourceModel\OrderTag\CollectionFactory as OrderTagCollectionFactory;

class OrderTagAttribute extends \Magento\Sales\Block\Adminhtml\Order\Create\AbstractCreate
{
    /**
     * @var OrderTagCollectionFactory
     */
    protected $orderTagCollectionFactory;


    /**
     * OrderTagAttribute constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param \Magento\Sales\Model\AdminOrder\Create $orderCreate
     * @param PriceCurrencyInterface $priceCurrency
     * @param OrderTagCollectionFactory $orderTagCollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Sales\Model\AdminOrder\Create $orderCreate,
        PriceCurrencyInterface $priceCurrency,
        OrderTagCollectionFactory $orderTagCollectionFactory,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $sessionQuote,
            $orderCreate,
            $priceCurrency,
            $data
        );

        $this->orderTagCollectionFactory = $orderTagCollectionFactory;
    }

    /**
     * Return Header CSS Class
     *
     * @return string
     */
    public function getHeaderCssClass()
    {
        return 'head-account';
    }

    /**
     * Return header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        return __('Order Tag');
    }

    /**
     * Check existing of payment methods
     *
     * @return bool
     */
    public function hasOrderTags()
    {
        $orderTags = $this->getOrderTags();
        if (is_array($orderTags) && count($orderTags)) {
            return true;
        }
        return false;
    }

    /**
     * Return Form Elements values
     *
     * @return array
     */
    public function getOrderTags()
    {
        $collection = $this->orderTagCollectionFactory->create();
        $orderTags = $collection->getItems();

        $data = [];
        /** @var \Vonnda\OrderTag\Model\OrderTag $orderTag */
        foreach ($orderTags as $orderTag) {
            if($orderTag->getVisible()){
                $data[$orderTag->getId()] = $orderTag->getLabel();
            }
        }

        return $data;
    }
}
