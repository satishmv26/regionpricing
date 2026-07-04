<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Plugin\Catalog\Model\Product;

use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Model\AbstractModel;
use SVExtensions\RegionPricing\Api\ProductRegionPriceRepositoryInterface;
use SVExtensions\RegionPricing\Helper\Logger;

class SavePlugin
{
    public function __construct(
        private readonly ProductRegionPriceRepositoryInterface $productRegionPriceRepository,
        private readonly Logger $logger
    ) {
    }

    public function aroundSave(
        ProductResource $subject,
        callable $proceed,
        AbstractModel $product
    ): ProductResource {
        $productId = (int)$product->getId();
        $hasRegionalPrices = $product->hasData('sv_regional_prices');

        $this->logger->info('SavePlugin - Before product save', [
            'product_id' => $productId,
            'has_regional_prices' => $hasRegionalPrices,
            'regional_prices_data' => $hasRegionalPrices ? $product->getData('sv_regional_prices') : null,
        ]);

        $result = $proceed($product);

        if ($hasRegionalPrices && $product->getId()) {
            $prices = $product->getData('sv_regional_prices');
            $this->logger->info('SavePlugin - Processing regional prices', [
                'product_id' => $product->getId(),
                'prices_data' => $prices,
            ]);

            if (is_array($prices)) {
                // Normalize: data may come as ['prices' => [...]] or as flat list
                if (isset($prices['prices'])) {
                    $prices = $prices['prices'];
                }
                try {
                    $this->logger->info('SavePlugin - Calling replaceForProduct', [
                        'product_id' => (int)$product->getId(),
                        'prices_count' => count($prices),
                    ]);
                    $this->productRegionPriceRepository->replaceForProduct(
                        (int)$product->getId(),
                        $prices
                    );
                    $this->logger->info('SavePlugin - Successfully saved regional prices', [
                        'product_id' => (int)$product->getId(),
                    ]);
                } catch (\Throwable $e) {
                    $this->logger->error('Failed to save regional prices', [
                        'product_id' => $product->getId(),
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        } else {
            $this->logger->info('SavePlugin - No regional prices to save', [
                'product_id' => $productId,
                'has_regional_prices' => $hasRegionalPrices,
            ]);
        }

        return $result;
    }
}
