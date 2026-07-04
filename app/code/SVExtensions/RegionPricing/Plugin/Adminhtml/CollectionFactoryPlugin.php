<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Plugin\Adminhtml;

use Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory;

class CollectionFactoryPlugin
{
    private const REGION_LISTING = 'sv_region_listing_data_source';
    private const REGION_COLLECTION = \SVExtensions\RegionPricing\Model\ResourceModel\Region\Grid\Collection::class;

    public function beforeGetReport(
        CollectionFactory $subject,
        string $requestName
    ): ?array {
        if ($requestName !== self::REGION_LISTING) {
            return null;
        }

        $reflection = new \ReflectionProperty($subject, 'collections');
        $reflection->setAccessible(true);
        $collections = $reflection->getValue($subject);
        if (!isset($collections[self::REGION_LISTING])) {
            $collections[self::REGION_LISTING] = self::REGION_COLLECTION;
            $reflection->setValue($subject, $collections);
        }

        return null;
    }
}
