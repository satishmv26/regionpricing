<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Api;

interface ProductRegionPriceRepositoryInterface
{
    /**
     * Get all regional prices for a product.
     * @param int $productId
     * @return \SVExtensions\RegionPricing\Api\Data\RegionalPriceInterface[]
     */
    public function getByProductId(int $productId): array;

    /**
     * Get regional price for a specific product and region.
     * @param int $productId
     * @param int $regionId
     * @return float|null
     */
    public function getPrice(int $productId, int $regionId): ?float;

    /**
     * Batch get regional prices for multiple products.
     * Returns [productId => price] map.
     * @param int[] $productIds
     * @param int $regionId
     * @return array
     */
    public function getPricesByProductIds(array $productIds, int $regionId): array;

    /**
     * Replace all regional prices for a product.
     * Transactional: inserts, updates, deletes as needed.
     * @param int $productId
     * @param array $prices [['region_id' => int, 'price' => float], ...]
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \InvalidArgumentException
     */
    public function replaceForProduct(int $productId, array $prices): void;
}
