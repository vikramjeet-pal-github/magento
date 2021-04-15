<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */
namespace Vonnda\Subscription\Ui\Component\Listing\Column;

use Vonnda\Subscription\Helper\SerializeHelper;
 
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
 
/**
 * Class SubscriptionhistoryChanges
 */
class SubscriptionhistoryChanges extends Column
{
    
    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param PriceCurrencyInterface $priceFormatter
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        SerializeHelper $serializeHelper,
        array $components = [],
        array $data = []
    ) {
        $this->serializeHelper = $serializeHelper;
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
            foreach ($dataSource['data']['items'] as & $item) {
                $changes = $this->serializeHelper->getAbbreviatedChanges($item['id']);
                $item[$this->getData('name')] = $changes;
            }
        }
 
        return $dataSource;
    }

}