<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use SVExtensions\RegionPricing\Api\Data\RegionPriceInfoInterface;
use SVExtensions\RegionPricing\Api\Data\RegionPriceInfoInterfaceFactory;
use SVExtensions\RegionPricing\Api\RegionPriceManagementInterface;
use SVExtensions\RegionPricing\Api\RegionRepositoryInterface;
use SVExtensions\RegionPricing\Api\RegionalPriceRepositoryInterface;
use SVExtensions\RegionPricing\Model\Config;

class RegionPriceManagement implements RegionPriceManagementInterface
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly RegionalPriceRepositoryInterface $regionalPriceRepository,
        private readonly RegionRepositoryInterface $regionRepository,
        private readonly RegionPriceInfoInterfaceFactory $regionPriceInfoFactory,
        private readonly Config $config
    ) {
    }

    public function getBySku(string $sku, ?string $regionCode = null)
    {
        if (!$this->config->isEnabled()) {
            return $this->fallbackResponse($sku);
        }

        try {
            $product = $this->productRepository->get($sku);
        } catch (NoSuchEntityException $e) {
            return $this->fallbackResponse($sku);
        }

        if (!$regionCode) {
            return $this->fallbackResponse($sku);
        }

        try {
            $region = $this->regionRepository->getByCode($regionCode);
        } catch (NoSuchEntityException $e) {
            return $this->fallbackResponse($sku);
        }

        $price = $this->regionalPriceRepository->getPriceByProductAndRegion(
            (int)$product->getId(),
            $region->getRegionId()
        );

        if ($price === null) {
            return $this->fallbackResponse($sku);
        }

        /** @var RegionPriceInfoInterface $info */
        $info = $this->regionPriceInfoFactory->create();
        $info->setSku($sku);
        $info->setPrice($price);
        $info->setCurrency($region->getCurrencyCode());
        $info->setIsFallback(false);

        return $info;
    }

    private function fallbackResponse(string $sku): RegionPriceInfoInterface
    {
        /** @var RegionPriceInfoInterface $info */
        $info = $this->regionPriceInfoFactory->create();
        $info->setSku($sku);
        $info->setPrice(null);
        $info->setIsFallback(true);
        return $info;
    }
}
