<?php

namespace Vonnda\OrderTag\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Vonnda\OrderTag\Model\OrderTagFactory;
use \Magento\Sales\Api\OrderRepositoryInterface;

class OrderTagLabel extends Column
{
    /**
     * @var OrderTagFactory
     */
    protected $orderTagFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * OrderTagLabel constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param OrderTagFactory $orderTagFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderTagFactory $orderTagFactory,
        OrderRepositoryInterface $orderRepository,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);

        $this->orderTagFactory = $orderTagFactory;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $order = $this->orderRepository->get($item["entity_id"]);
                $orderTagId = $order->getOrderTagId();

                if (!$orderTagId) {
                    $item[$this->getData('name')] = __("Not defined");
                } else {
                    /** @var  $collection */
                    $collection = $this->orderTagFactory->create()->getCollection();
                    $collection->addFieldToFilter('entity_id', $orderTagId);

                    $data = $collection->getFirstItem();

                    $item[$this->getData('name')] = $data->getLabel();
                }
            }
        }

        return $dataSource;
    }
}
