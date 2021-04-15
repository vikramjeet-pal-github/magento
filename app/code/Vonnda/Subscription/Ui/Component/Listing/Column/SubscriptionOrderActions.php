<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */
namespace Vonnda\Subscription\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

/**
 * Class SubscriptionOrderActions
 */
class SubscriptionOrderActions extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
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
            foreach ($dataSource['data']['items'] as &$item) {
                $item[$this->getData('name')]['view_subscription_customer'] = [
                    'href' => $this->urlBuilder->getUrl(
                        'vonnda_subscription/subscriptioncustomer/edit',
                        ['id' => $item['subscription_customer_id']]
                    ),
                    'label' => __('View Subscription'),
                    'hidden' => false,
                ];

                $item[$this->getData('name')]['view_order'] = [
                    'href' => $this->urlBuilder->getUrl(
                        'sales/order/view',
                        ['order_id' => $item['order_id']]
                    ),
                    'label' => __('View Order'),
                    'hidden' => false,
                ];
            }
        }

        return $dataSource;
    }
}