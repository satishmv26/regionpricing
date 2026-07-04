<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Model\Resolver;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use SVExtensions\RegionPricing\Api\Data\RegionalPriceInterface;
use SVExtensions\RegionPricing\Api\RegionalPriceRepositoryInterface;
use SVExtensions\RegionPricing\Api\RegionRepositoryInterface;

class RegionalPrices implements ResolverInterface
{
    public function __construct(
        private readonly RegionalPriceRepositoryInterface $regionalPriceRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly RegionRepositoryInterface $regionRepository
    ) {
    }

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $regionCodeFilter = $args['regionCode'] ?? null;
        $skuFilter = $args['sku'] ?? null;

        $regionIds = null;
        if ($regionCodeFilter) {
            try {
                $region = $this->regionRepository->getByCode($regionCodeFilter);
                $regionIds = [(int)$region->getRegionId()];
            } catch (NoSuchEntityException $e) {
                return [];
            }
        }

        $searchCriteria = new \Magento\Framework\Api\SearchCriteria();

        $items = $this->regionalPriceRepository
            ->getList($searchCriteria)
            ->getItems();

        $result = [];
        foreach ($items as $item) {
            if ($regionIds !== null && !in_array((int)$item->getRegionId(), $regionIds, true)) {
                continue;
            }

            $sku = '';
            try {
                $product = $this->productRepository->getById($item->getProductId());
                $sku = $product->getSku();
            } catch (\Exception $e) {
                $sku = '';
            }

            if ($skuFilter !== null && $sku !== $skuFilter) {
                continue;
            }

            $regionCode = null;
            $regionName = null;
            try {
                $region = $this->regionRepository->getById((int)$item->getRegionId());
                $regionCode = $region->getCode();
                $regionName = $region->getName();
            } catch (NoSuchEntityException $e) {
            }

            $result[] = [
                'entity_id' => $item->getEntityId(),
                'product_id' => $item->getProductId(),
                'region_id' => $item->getRegionId(),
                'price' => $item->getPrice(),
                'sku' => $sku,
                'region_code' => $regionCode,
                'region_name' => $regionName,
            ];
        }

        return $result;
    }
}
