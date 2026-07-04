<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AssignCustomerRegionIdToAttributeSet implements DataPatchInterface
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

        $attributeData = $customerSetup->getAttribute(Customer::ENTITY, 'sv_region_id');
        if (!$attributeData || empty($attributeData['attribute_id'])) {
            return;
        }

        $customerSetup->addAttributeToSet(
            Customer::ENTITY,
            '1',
            'General',
            'sv_region_id',
            210
        );
    }

    public static function getDependencies(): array
    {
        return [
            \SVExtensions\RegionPricing\Setup\Patch\Data\AddCustomerRegionIdAttribute::class,
        ];
    }

    public function getAliases(): array
    {
        return [];
    }
}
