<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Api;

interface RegionPriceManagementInterface
{
    /**
     * Get regional price for a product by SKU and optional region code.
     *
     * @param string $sku
     * @param string|null $regionCode
     * @return \SVExtensions\RegionPricing\Api\Data\RegionPriceInfoInterface|null
     */
    public function getBySku(string $sku, ?string $regionCode = null);
}
