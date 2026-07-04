<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use SVExtensions\RegionPricing\Api\RegionPricingFacadeInterface;

class ActiveRegions implements ResolverInterface
{
    public function __construct(
        private readonly RegionPricingFacadeInterface $regionPricingFacade
    ) {
    }

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $regions = $this->regionPricingFacade->getActiveRegions();
        $result = [];

        foreach ($regions as $region) {
            $result[] = [
                'region_id' => $region->getRegionId(),
                'name' => $region->getName(),
                'code' => $region->getCode(),
                'currency_code' => $region->getCurrencyCode(),
                'status' => $region->getStatus(),
            ];
        }

        return $result;
    }
}
