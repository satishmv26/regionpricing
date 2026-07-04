<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Api;

interface RegionPricingFacadeInterface
{
    /**
     * Return all active (enabled) regions.
     * Public — no auth required.
     * @return \SVExtensions\RegionPricing\Api\Data\RegionInterface[]
     */
    public function getActiveRegions(): array;

    /**
     * Return the currently resolved region for the visitor.
     * Public — no auth required.
     * @return \SVExtensions\RegionPricing\Api\Data\RegionInterface|null
     */
    public function getCurrentRegion();
}
