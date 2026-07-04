<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Model;

use SVExtensions\RegionPricing\Api\RegionPricingFacadeInterface;

class RegionPricingFacade implements RegionPricingFacadeInterface
{
    public function __construct(
        private readonly RegionProvider $regionProvider
    ) {
    }

    public function getActiveRegions(): array
    {
        return $this->regionProvider->getActiveRegions();
    }

    public function getCurrentRegion()
    {
        return $this->regionProvider->getCurrentRegion();
    }
}
