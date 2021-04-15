<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Block\Adminhtml\Rule\Edit\Tab;

use Amasty\Base\Helper\Module;
use Amasty\Shiprules\Model\ConstantsInterface;

/**
 * UI configuration of Rule Action selector.
 */
class Actions extends \Amasty\CommonRules\Block\Adminhtml\Rule\Edit\Tab\AbstractTab
{
    const FORM_NAME = 'amasty_shiprules_form';

    const RULE_ACTIONS_FIELDSET_NAMESPACE = 'rule_actions_fieldset';

    /**
     * @var \Magento\Rule\Block\Actions
     */
    private $actions;

    /**
     * @var \Magento\Backend\Block\Widget\Form\Renderer\Fieldset
     */
    private $rendererFieldset;

    /**
     * @var Module
     */
    private $moduleHelper;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Amasty\CommonRules\Model\OptionProvider\Pool $poolOptionProvider,
        \Magento\Rule\Block\Actions $actions,
        \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $rendererFieldset,
        Module $moduleHelper,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $poolOptionProvider, $data);

        $this->actions = $actions;
        $this->rendererFieldset = $rendererFieldset;
        $this->moduleHelper = $moduleHelper;
    }

    public function _construct()
    {
        $this->setRegistryKey(ConstantsInterface::REGISTRY_KEY);

        parent::_construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function getLabel()
    {
        return __('Conditions');
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return \Amasty\CommonRules\Block\Adminhtml\Rule\Edit\Tab\AbstractTab| $this
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        $model = $this->getModel();
        $form = $this->formInit($model);
        $form->setValues($model->getData());
        $form->addValues(['id' => $model->getId()]);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * @return mixed
     */
    protected function getModel()
    {
        return $this->_coreRegistry->registry($this->registryKey);
    }

    /**
     * @inheritdoc
     */
    protected function formInit($model)
    {
        $form = $this->_formFactory->create();
        $renderer = $this->rendererFieldset->setTemplate(
            'Amasty_CommonRules::ui/conditions/fieldset.phtml'
        )->setFieldSetId(self::RULE_ACTIONS_FIELDSET_NAMESPACE)->setNewChildUrl(
            $this->getUrl(
                '*/*/newActionHtml',
                ['form' => self::FORM_NAME, 'form_namespace' => self::RULE_ACTIONS_FIELDSET_NAMESPACE]
            )
        );

        $guideLink = $this->escapeHtml(
            'https://amasty.com/docs/doku.php?id=magento_2:shipping-rules'
            . '&utm_source=extension&utm_medium=hint&utm_campaign=shrules-m2-04_1#products'
        );
        $postLink = $this->escapeHtml(
            'http://amasty.com/blog/use-magento-rules-properly-common-mistakes-corrected/'
            . '?utm_source=extension&utm_medium=hint&utm_campaign=shrules-m2-04_2'
        );

        $comment = 'Check our <a target="_blank" title="User Guide" href="' . $guideLink
            . '">user guide</a> to set the conditions properly.';

        if (!$this->moduleHelper->isOriginMarketplace()) {
            $comment .= ' Also,  <a target="_blank" title="Blog Post" href="'
                . $postLink . '">this post</a> in our blog will help you to avoid common mistakes.';
        }

        $fieldset = $form->addFieldset(
            self::RULE_ACTIONS_FIELDSET_NAMESPACE,
            [
                'legend' => __(
                    'Apply the rule only to cart items matching the following conditions (leave blank for all items).'
                ),
                'comment' => $comment
            ]
        )->setRenderer($renderer);

        $fieldset->addField(
            'conditions',
            'text',
            [
                'name' => 'conditions',
                'label' => __('Conditions'),
                'title' => __('Conditions'),
                'data-form-part' => self::FORM_NAME
            ]
        )->setRule($model)->setRenderer($this->actions);

        $this->setActionFormName($model->getActions(), self::RULE_ACTIONS_FIELDSET_NAMESPACE, self::FORM_NAME);

        return $form;
    }

    /**
     * @param \Magento\Rule\Model\Condition\AbstractCondition $actions
     * @param string $fieldsetName
     * @param string $formName
     *
     * @return void
     */
    protected function setActionFormName(
        \Magento\Rule\Model\Condition\AbstractCondition $actions,
        $fieldsetName,
        $formName
    ) {
        $actions->setFormName($formName);
        $actions->setJsFormObject($fieldsetName);

        if ($actions->getActions() && is_array($actions->getActions())) {
            foreach ($actions->getActions() as $condition) {
                $this->setActionFormName($condition, $fieldsetName, $formName);
            }
        }
    }
}
