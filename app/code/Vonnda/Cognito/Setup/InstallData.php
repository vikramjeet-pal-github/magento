<?php
namespace Vonnda\Cognito\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Customer\Model\Customer;

class InstallData implements InstallDataInterface
{
    /** @var ModuleDataSetupInterface */
    protected $moduleDataSetup;

    /** @var CustomerSetupFactory */
    protected $customerSetupFactory;

    /** @var AttributeSetFactory */
    protected $attributeSetFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory $customerSetupFactory
     * @param AttributeSetFactory $attributeSetFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory,
        AttributeSetFactory $attributeSetFactory
    ){
        $this->moduleDataSetup = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $customerSetup->addAttribute(Customer::ENTITY, 'cognito_uuid', [
            'type' => 'varchar',
            'label' => 'Cognito UUID',
            'input' => 'text',
            'required' => false,
            'position' => 999,
            'visible' => true,
            'user_defined' => true,
            'unique' => false,
            'system' => false
        ]);
        $customerEntity = $customerSetup->getEavConfig()->getEntityType(Customer::ENTITY);
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();
        $attributeSet = $this->attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);
        $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'cognito_uuid');
        $attribute->addData([
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId,
            'used_in_forms' => ['adminhtml_customer', 'customer_account_create', 'customer_account_edit'],
        ]);
        $attribute->save();
    }

}
