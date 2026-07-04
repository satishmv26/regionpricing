<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Plugin\Catalog\Controller\Adminhtml\Product;

use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper;
use Magento\Framework\App\RequestInterface;
use SVExtensions\RegionPricing\Helper\Logger;

class InitializationPlugin
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly Logger $logger
    ) {
    }

    public function afterInitialize(
        Helper $subject,
        $product
    ) {
        $data = $this->request->getPostValue();

        $this->logger->info('InitializationPlugin - POST data structure', [
            'has_product_key' => isset($data['product']),
            'has_sv_regional_prices' => isset($data['product']['sv_regional_prices']),
            'has_prices' => isset($data['product']['sv_regional_prices']['prices']),
            'product_keys' => isset($data['product']) ? array_keys($data['product']) : [],
            'sv_regional_prices_content' => isset($data['product']['sv_regional_prices']) ? $data['product']['sv_regional_prices'] : 'N/A',
        ]);

        if (isset($data['product']['sv_regional_prices']['prices'])) {
            $prices = [];
            foreach ($data['product']['sv_regional_prices']['prices'] as $row) {
                if (isset($row['region_id'], $row['price'])
                    && $row['region_id'] !== ''
                    && $row['price'] !== ''
                ) {
                    $prices[] = [
                        'region_id' => (int)$row['region_id'],
                        'price' => (float)$row['price'],
                    ];
                }
            }
            if (!empty($prices)) {
                $this->logger->info('InitializationPlugin - Setting regional prices on product', [
                    'product_id' => $product->getId(),
                    'prices_count' => count($prices),
                    'prices' => $prices,
                ]);
                $product->setData('sv_regional_prices', $prices);
            }
        } else {
            $this->logger->info('InitializationPlugin - No regional prices found in POST data');
        }

        return $product;
    }
}
