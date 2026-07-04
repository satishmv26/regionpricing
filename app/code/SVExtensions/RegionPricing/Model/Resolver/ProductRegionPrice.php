<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use SVExtensions\RegionPricing\Api\RegionRepositoryInterface;
use SVExtensions\RegionPricing\Model\PriceResolver;
use SVExtensions\RegionPricing\Model\RegionProvider;

class ProductRegionPrice implements ResolverInterface
{
    public function __construct(
        private readonly PriceResolver $priceResolver,
        private readonly RegionProvider $regionProvider,
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
        if (!$value || !isset($value['model'])) {
            return null;
        }

        $product = $value['model'];
        $regionCode = $args['region'] ?? null;

        $productId = (int)$product->getId();

        if ($regionCode) {
            $price = $this->priceResolver->resolvePriceBySku($product->getSku(), $regionCode);
        } else {
            $price = $this->priceResolver->resolvePrice($productId);
        }

        if ($price === null) {
            return null;
        }

        $region = $this->regionProvider->getCurrentRegion();
        $responseRegionId = $region ? $region->getRegionId() : null;
        $responseRegionCode = $region ? $region->getCode() : null;
        $responseRegionName = $region ? $region->getName() : null;

        if ($regionCode) {
            try {
                $requestedRegion = $this->regionRepository->getByCode($regionCode);
                $responseRegionId = (int)$requestedRegion->getRegionId();
                $responseRegionCode = $requestedRegion->getCode();
                $responseRegionName = $requestedRegion->getName();
            } catch (NoSuchEntityException $e) {
            }
        }

        return [
            'entity_id' => null,
            'product_id' => $productId,
            'region_id' => $responseRegionId,
            'price' => $price,
            'sku' => $product->getSku(),
            'region_code' => $responseRegionCode,
            'region_name' => $responseRegionName,
        ];
    }
}
