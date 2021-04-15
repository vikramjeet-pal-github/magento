<?php
namespace Vonnda\Checkout\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Catalog\Api\AttributeSetManagementInterface;
use Magento\Eav\Api\Data\AttributeSetInterfaceFactory;
use Magento\Catalog\Api\Data\ProductAttributeInterface;

class UpgradeData implements UpgradeDataInterface
{

    /** @var AttributeSetManagementInterface */
    private $attributeSetManagement;

    /** @var AttributeSetInterfaceFactory */
    private $attributeSetFactory;

    /**
     * @param AttributeSetManagementInterface $attributeSetManagement
     * @param AttributeSetInterfaceFactory $attributeSetFactory
     */
    public function __construct(
        AttributeSetManagementInterface $attributeSetManagement,
        AttributeSetInterfaceFactory $attributeSetFactory
    ) {
        $this->attributeSetManagement = $attributeSetManagement;
        $this->attributeSetFactory = $attributeSetFactory;
    }

    /** {@inheritdoc} */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            try {
                /** @var \Magento\Eav\Api\Data\AttributeSetInterface $attributeSet */
                $attributeSet = $this->attributeSetFactory->create();
                $attributeSet->setAttributeSetName('Device');
                $attributeSet->setEntityTypeId(ProductAttributeInterface::ENTITY_TYPE_CODE);
                $this->attributeSetManagement->create($attributeSet, 4);
            } catch (\Magento\Framework\Exception\InputException $e) {
                // catching error thrown if attribute set with name 'Device' already exists
                // don't need to do anything, attribute set is there, and this catch will allow the update to finish
            }
        }
        $setup->endSetup();
    }

}