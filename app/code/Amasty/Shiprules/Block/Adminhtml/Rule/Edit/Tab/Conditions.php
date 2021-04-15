<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Block\Adminhtml\Rule\Edit\Tab;

use Amasty\Base\Helper\Module;
use Amasty\Shiprules\Model\ConstantsInterface;
use Amasty\CommonRules\Block\Adminhtml\Rule\Edit\Tab\Conditions as CommonRulesCondition;

/**
 * UI configuration of Rule Conditions selector.
 */
class Conditions extends CommonRulesCondition
{
    const FORM_NAME = 'amasty_shiprules_form';

    /**
     * @var string
     */
    protected $_nameInLayout = 'conditions_apply_to';

    /**
     * @var Module
     */
    private $moduleHelper;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Amasty\CommonRules\Model\OptionProvider\Pool $poolOptionProvider,
        \Magento\Rule\Block\Conditions $conditions,
        \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $rendererFieldset,
        Module $moduleHelper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $poolOptionProvider,
            $conditions,
            $rendererFieldset,
            $data
        );
        $this->moduleHelper = $moduleHelper;
    }

    public function _construct()
    {
        $this->setRegistryKey(ConstantsInterface::REGISTRY_KEY);
        parent::_construct();
    }

    /**
     * @inheritdoc
     */
    protected function formInit($model)
    {
        $form = $this->_formFactory->create();
        $renderer = $this->rendererFieldset->setTemplate(
            'Amasty_CommonRules::ui/conditions/fieldset.phtml'
        )->setFieldSetId(self::RULE_CONDITIONS_FIELDSET_NAMESPACE)->setNewChildUrl(
            $this->getUrl(
                'amasty_rules/rule/newConditionHtml',
                ['form' => self::RULE_CONDITIONS_FIELDSET_NAMESPACE, 'form_namespace' => self::FORM_NAME]
            )
        );

        $guideLink = $this->escapeHtml(
            'https://amasty.com/docs/doku.php?id=magento_2:shipping-rules'
            . '&utm_source=extension&utm_medium=hint&utm_campaign=shrules-m2-15#conditions'
        );
        $postLink = $this->escapeHtml(
            'http://amasty.com/blog/use-magento-rules-properly-common-mistakes-corrected/'
            . '?utm_source=extension&utm_medium=hint&utm_campaign=shrules-m2-15_2'
        );

        $comment = 'Check our <a target="_blank" title="User Guide" href="' . $guideLink
            . '">user guide</a> to set the conditions properly.';

        if (!$this->moduleHelper->isOriginMarketplace()) {
            $comment .= ' Also,  <a target="_blank" title="Blog Post" href="'
                . $postLink . '">this post</a> in our blog will help you to avoid common mistakes.';
        }

        $fieldset = $form->addFieldset(
            self::RULE_CONDITIONS_FIELDSET_NAMESPACE,
            [
                'legend' => __(
                    'Apply the rule only if the following conditions are met (leave blank for all products).'
                ),
                'comment' => $comment,
            ]
        )->setRenderer(
            $renderer
        );

        $fieldset->addField(
            'conditions',
            'text',
            [
                'name' => 'conditions',
                'label' => __('Conditions'),
                'title' => __('Conditions'),
                'data-form-part' => self::FORM_NAME
            ]
        )->setRule(
            $model
        )->setRenderer(
            $this->conditions
        );

        $this->setConditionFormName($model->getConditions(), self::RULE_CONDITIONS_FIELDSET_NAMESPACE, self::FORM_NAME);

        return $form;
    }
}
