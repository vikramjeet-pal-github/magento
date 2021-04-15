<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_ShippingTableRates
 */


namespace Amasty\ShippingTableRates\Plugin\ImportExport\Block\Adminhtml\Import\Edit;

use Amasty\ShippingTableRates\Model\ResourceModel\Method\CollectionFactory;
use Magento\ImportExport\Block\Adminhtml\Import\Edit\Form as ImportExportForm;

class Form
{
    /**
     * @var CollectionFactory
     */
    private $methodsCollection;

    public function __construct(
        CollectionFactory $methodsCollection
    ) {
        $this->methodsCollection = $methodsCollection;
    }

    /**
     * @param ImportExportForm $subject
     *
     * @return void
     */
    public function beforeGetFormHtml($subject)
    {
        if ($subject->getForm()->getElement('amastratebasic_behavior_fieldset')
            && !$subject->getForm()->getElement('amastrate_methods')
        ) {
            $methodsCollection = $this->methodsCollection->create();
            $methods = $methodsCollection->hashMethodsName();

            $fieldset = $subject->getForm()->addFieldset(
                'amastrate_methods',
                ['legend' => __('Shipping Table Rate Methods')],
                'amastratebasic_behavior_fieldset'
            );

            $fieldset->addField(
                'amastrate_method',
                'select',
                [
                    'name' => 'amastrate_method',
                    'title' => __('Shipping Table Rate Method'),
                    'label' => __('Shipping Table Rate Method'),
                    'required' => true,
                    'values' => $methods
                ]
            );
        }
    }
}
