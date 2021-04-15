<?php
/**
 * Copyright © 2020 Grazitti. All rights reserved.
 */

namespace Grazitti\Maginate\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Grazitti\Maginate\Block\Adminhtml\Module\Grid\Renderer\Action\UrlBuilder;
use Magento\Framework\UrlInterface;

class Ordersync extends Column
{
    /** Url path */
    const URL_PATH_SYNC = 'maginate/order/ordersync';

    /** @var UrlBuilder */
    protected $actionUrlBuilder;
    public $scopeConfig;
    /** @var UrlInterface */
    protected $urlBuilder;
    protected $_objectManager;
    protected $_orderModel;
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlBuilder $actionUrlBuilder
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlBuilder $actionUrlBuilder,
        UrlInterface $urlBuilder,
        \Magento\Sales\Model\Order $orderModel,
        array $components = [],
        array $data = []
    ) {
        $this->_objectManager = $objectmanager;
        $this->urlBuilder = $urlBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->actionUrlBuilder = $actionUrlBuilder;
        $this->_orderModel = $orderModel;
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
        $enable=$this->scopeConfig->getValue(
            'grazitti_maginate/orderconfig/maginate_order_integration',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                
                $name = $this->getData('name');
                if (isset($item['entity_id'])) {
                    $order = $this->_orderModel->load($item['entity_id']);
                    $alreadySync = $order->getSyncWithMarketo();
                    if ($enable==1) {
                        if ($alreadySync == 1) {
                            $item[$name]['delete'] = [
                                'href' => 'javascript:void(0)',
                                'label' => __('Already Synced with Marketo'),
                            ];
                        } else {
                            $item[$name]['delete'] = [
                            'href' => $this->urlBuilder->getUrl(
                                self::URL_PATH_SYNC,
                                ['entity_id' => $item['entity_id']]
                            ),
                            'label' => __('Sync with Marketo'),
                            'confirm' => [
                                'title' => __('Sync '.$item['increment_id']),
                                'message' => __('Are you sure you want to Sync this order with Marketo?')
                            ]
                            ];
                        }
                    }
                }
            }
        }
        return $dataSource;
    }
    public function prepare()
    {
        parent::prepare();
        $enable=$this->scopeConfig->getValue(
            'grazitti_maginate/orderconfig/maginate_order_integration',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if ($enable!=1) {
            $this->_data['config']['componentDisabled'] = true;
        }
    }
}
