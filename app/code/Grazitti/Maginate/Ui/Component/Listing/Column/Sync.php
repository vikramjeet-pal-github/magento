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

class Sync extends Column
{
    /** Url path */
    const URL_PATH_SYNC = 'maginate/order/sync';

    /** @var UrlBuilder */
    protected $actionUrlBuilder;

    /** @var UrlInterface */
    protected $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlBuilder $actionUrlBuilder
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    protected $_objectManager;
    public $scopeConfig;
    protected $_orderModel;
    protected $_modelData;
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlBuilder $actionUrlBuilder,
        UrlInterface $urlBuilder,
        \Magento\Sales\Model\Order $orderModel,
        \Grazitti\Maginate\Model\Data $modelData,
        array $components = [],
        array $data = []
    ) {
        $this->_objectManager = $objectmanager;
        $this->urlBuilder = $urlBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->actionUrlBuilder = $actionUrlBuilder;
        $this->_orderModel = $orderModel;
        $this->_modelData = $modelData;
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
            'grazitti_maginate/general/maginate_lead_sync_on_login',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        
        if (isset($dataSource['data']['items'])) {
           
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');
                if (isset($item['entity_id'])) {
                    $cust_id = $item['entity_id'];
                    $sync_records = $this->_modelData;
                    $sync_collection = $sync_records->getCollection()->addFieldToFilter(
                        'customer_id',
                        ['eq' => "$cust_id"]
                    );
                    $recordCount = count($sync_collection);
                    if ($enable==1) {
                        if ($recordCount == 0) {
                            $item[$name]['delete'] = [
                            'href' => $this->urlBuilder->getUrl(
                                self::URL_PATH_SYNC,
                                ['entity_id' => $item['entity_id']]
                            ),
                            'label' => __('Sync With marketo'),
                            'confirm' => [
                                'title' => __('Sync '.$item['name']),
                                'message' => __('Are you sure you want to Sync this customer with Marketo?')
                            ]
                            ];
                        } else {
                            $item[$name]['delete'] = [
                            'href' => 'javascript:void(0)',
                            'label' => __('Already synced With Marketo'),
                            'disable' => 'disabled'
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
            'grazitti_maginate/general/maginate_lead_sync_on_login',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if ($enable!=1) {
            $this->_data['config']['componentDisabled'] = true;
        }
    }
}
