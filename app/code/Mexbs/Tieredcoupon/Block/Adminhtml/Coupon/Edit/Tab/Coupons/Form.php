<?php
namespace Mexbs\Tieredcoupon\Block\Adminhtml\Coupon\Edit\Tab\Coupons;


class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
    }


    protected function _prepareForm()
    {
        /**
         * @var \Magento\Framework\Data\Form $form
         */
        $form = $this->_formFactory->create();

        $tieredcoupon = $this->_coreRegistry->registry(\Mexbs\Tieredcoupon\Model\RegistryConstants::CURRENT_COUPON);
        $tieredcouponId = $tieredcoupon->getId();

        $form->setHtmlIdPrefix('coupons_');

        $gridBlock = $this->getLayout()->getBlock('tieredcoupon_edit_tab_coupons_grid');
        $gridBlockJsObject = '';
        if ($gridBlock) {
            $gridBlockJsObject = $gridBlock->getJsObjectName();
        }

        $fieldset = $form->addFieldset('information_fieldset', []);
        $fieldset->addClass('ignore-validate');

        $fieldset->addField('id', 'hidden', ['name' => 'id', 'value' => $tieredcouponId]);

        $fieldset->addField(
            'qty',
            'text',
            [
                'name' => 'qty',
                'label' => __('Coupon Qty'),
                'title' => __('Coupon Qty'),
                'required' => true,
                'disabled' => !$tieredcouponId,
                'class' => 'validate-digits validate-greater-than-zero'
            ]
        );

        $fieldset->addField(
            'length',
            'text',
            [
                'name' => 'length',
                'label' => __('Code Length'),
                'title' => __('Code Length'),
                'required' => true,
                'disabled' => !$tieredcouponId,
                'note' => __('Excluding prefix, suffix and separators.'),
                'value' => 12,
                'class' => 'validate-digits validate-greater-than-zero'
            ]
        );

        $fieldset->addField(
            'format',
            'select',
            [
                'label' => __('Code Format'),
                'name' => 'format',
                'options' => [
                    \Magento\SalesRule\Helper\Coupon::COUPON_FORMAT_ALPHANUMERIC => __('Alphanumeric'),
                    \Magento\SalesRule\Helper\Coupon::COUPON_FORMAT_ALPHABETICAL => __('Alphabetical'),
                    \Magento\SalesRule\Helper\Coupon::COUPON_FORMAT_NUMERIC => __('Numeric')
                ],
                'required' => true,
                'disabled' => !$tieredcouponId,
                'value' => \Magento\SalesRule\Helper\Coupon::COUPON_FORMAT_ALPHANUMERIC
            ]
        );

        $fieldset->addField(
            'prefix',
            'text',
            [
                'name' => 'prefix',
                'label' => __('Code Prefix'),
                'title' => __('Code Prefix'),
                'value' => '',
                'disabled' => !$tieredcouponId,
            ]
        );

        $fieldset->addField(
            'suffix',
            'text',
            [
                'name' => 'suffix',
                'label' => __('Code Suffix'),
                'title' => __('Code Suffix'),
                'value' => '',
                'disabled' => !$tieredcouponId,
            ]
        );

        $fieldset->addField(
            'dash',
            'text',
            [
                'name' => 'dash',
                'label' => __('Dash Every X Characters'),
                'title' => __('Dash Every X Characters'),
                'note' => __('If empty no separation.'),
                'value' => 0,
                'class' => 'validate-digits',
                'disabled' => !$tieredcouponId,
            ]
        );

        $idPrefix = $form->getHtmlIdPrefix();
        $generateUrl = $this->getGenerateUrl();

        $fieldset->addField(
            'generate_button',
            'note',
            [
                'text' => $this->getButtonHtml(
                    __('Generate'),
                    "generateCouponCodes('{$idPrefix}' ,'{$generateUrl}', '{$gridBlockJsObject}')",
                    'generate'.(!$tieredcouponId ? " disabled": "")
                )
            ]
        );

        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Retrieve URL to Generate Action
     *
     * @return string
     */
    public function getGenerateUrl()
    {
        return $this->getUrl('tieredcoupon/coupon/generate');
    }
}
