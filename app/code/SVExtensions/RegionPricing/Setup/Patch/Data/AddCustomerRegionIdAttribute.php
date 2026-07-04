<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddCustomerRegionIdAttribute implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly CustomerSetupFactory $customerSetupFactory,
        private readonly AttributeSetFactory $attributeSetFactory
    ) {
    }

    public function apply(): void
    {
        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $customerSetup->addAttribute(
            Customer::ENTITY,
            'sv_region_id',
            [
                'type' => 'int',
                'label' => 'Pricing Region',
                'input' => 'select',
                'source' => \SVExtensions\RegionPricing\Model\Customer\Attribute\Source\Region::class,
                'required' => false,
                'visible' => true,
                'system' => false,
                'user_defined' => true,
                'position' => 210,
                'sort_order' => 210,
                'group' => 'General',
            ]
        );

        $usedInForms = [
            'adminhtml_customer',
            'customer_account_create',
            'customer_account_edit',
        ];

        foreach ($usedInForms as $formCode) {
            $customerSetup->getSetup()
                ->getConnection()
                ->insertOnDuplicate(
                    $customerSetup->getSetup()->getTable('customer_form_attribute'),
                    [
                        'form_code' => $formCode,
                        'attribute_id' => $customerSetup->getAttributeId(Customer::ENTITY, 'sv_region_id'),
                    ]
                );
        }
    }

    public static function getDependencies(): array
    {
        return [
            \SVExtensions\RegionPricing\Setup\Patch\Data\AddCustomerAttribute::class,
        ];
    }

    public function getAliases(): array
    {
        return [];
    }
}
