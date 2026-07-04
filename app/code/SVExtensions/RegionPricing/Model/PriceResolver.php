<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use SVExtensions\RegionPricing\Api\ProductRegionPriceRepositoryInterface;
use SVExtensions\RegionPricing\Api\RegionRepositoryInterface;
use SVExtensions\RegionPricing\Model\RegionProvider;
class PriceResolver
{
    protected array $resolvedCache = [];
    protected ?int $currentRegionId = null;

    public function __construct(
        protected readonly Config $config,
        protected readonly RegionProvider $regionProvider,
        protected readonly ProductRegionPriceRepositoryInterface $productRegionPriceRepository,
        protected readonly ProductRepositoryInterface $productRepository,
        protected readonly RegionRepositoryInterface $regionRepository
    ) {
    }

    /**
     * Get regional price for a single product.
     *
     * Returns:
     * - Regional price when module is enabled, customer has a valid region,
     *   and product has a price for that region.
     * - null otherwise, allowing native Magento pricing fallback.
     *
     * Uses request-level memoization, including null results.
     */
    public function resolvePrice(int $productId): ?float
    {
        if (!$this->config->isEnabled()) {
            return null;
        }

        $regionId = $this->getCurrentRegionId();

        if ($regionId === null) {
            return null;
        }

        $cacheKey = $regionId . ':' . $productId;

        if (array_key_exists($cacheKey, $this->resolvedCache)) {
            return $this->resolvedCache[$cacheKey];
        }

        $price = $this->productRegionPriceRepository->getPrice(
            $productId,
            $regionId
        );

        $this->resolvedCache[$cacheKey] = $price !== null
            ? (float)$price
            : null;

        return $this->resolvedCache[$cacheKey];
    }

    /**
     * Batch resolve regional prices for multiple products.
     * Performs ONE database query.
     */
    public function resolvePrices(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        if (!$this->config->isEnabled()) {
            return array_fill_keys($productIds, null);
        }

        $regionId = $this->getCurrentRegionId();
        if (!$regionId) {
            return array_fill_keys($productIds, null);
        }

        $uncached = [];
        foreach ($productIds as $id) {
            if (!array_key_exists($id, $this->resolvedCache)) {
                $uncached[] = $id;
            }
        }

        if (!empty($uncached)) {
            $prices = $this->productRegionPriceRepository->getPricesByProductIds($uncached, $regionId);
            foreach ($productIds as $id) {
                $this->resolvedCache[$id] = $prices[$id] ?? null;
            }
        }

        $result = [];
        foreach ($productIds as $id) {
            $result[$id] = $this->resolvedCache[$id] ?? null;
        }
        return $result;
    }

    public function resolvePriceBySku(string $sku, ?string $regionCode = null): ?float
    {
        if (!$this->config->isEnabled()) {
            return null;
        }

        try {
            $product = $this->productRepository->get($sku);
        } catch (NoSuchEntityException $e) {
            return null;
        }

        $regionId = $this->resolveRegionId($regionCode);
        if (!$regionId) {
            return null;
        }

        return $this->productRegionPriceRepository->getPrice(
            (int)$product->getId(),
            $regionId
        );
    }

    protected function getCurrentRegionId(): ?int
    {
        return $this->regionProvider->getCurrentRegionId();
    }

    protected function resolveRegionId(?string $regionCode): ?int
    {
        if ($regionCode) {
            try {
                $region = $this->regionRepository->getByCode($regionCode);
                return $region->getRegionId();
            } catch (NoSuchEntityException $e) {
                return null;
            }
        }
        return $this->getCurrentRegionId();
    }

    /**
     * Clear internal caches (useful between test runs).
     */
    public function clearCache(): void
    {
        $this->resolvedCache = [];
        $this->currentRegionId = null;
    }
}
