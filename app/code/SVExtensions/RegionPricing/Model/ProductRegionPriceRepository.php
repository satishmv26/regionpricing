<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\ResourceConnection;
use SVExtensions\RegionPricing\Api\Data\RegionalPriceInterface;
use SVExtensions\RegionPricing\Api\Data\RegionalPriceInterfaceFactory;
use SVExtensions\RegionPricing\Api\ProductRegionPriceRepositoryInterface;
use SVExtensions\RegionPricing\Api\RegionRepositoryInterface;
use SVExtensions\RegionPricing\Model\ResourceModel\RegionalPrice as RegionalPriceResource;
use SVExtensions\RegionPricing\Model\ResourceModel\RegionalPrice\CollectionFactory;

class ProductRegionPriceRepository implements ProductRegionPriceRepositoryInterface
{
    private array $byProductCache = [];

    public function __construct(
        private readonly RegionalPriceInterfaceFactory $priceFactory,
        private readonly RegionalPriceResource $resource,
        private readonly CollectionFactory $collectionFactory,
        private readonly RegionRepositoryInterface $regionRepository,
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    public function getByProductId(int $productId): array
    {
        if (isset($this->byProductCache[$productId])) {
            return $this->byProductCache[$productId];
        }

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(RegionalPriceInterface::PRODUCT_ID, $productId);

        $items = $collection->getItems();
        $this->byProductCache[$productId] = $items;
        return $items;
    }

    public function getPrice(int $productId, int $regionId): ?float
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(RegionalPriceInterface::PRODUCT_ID, $productId);
        $collection->addFieldToFilter(RegionalPriceInterface::REGION_ID, $regionId);
        $collection->setPageSize(1);

        $item = $collection->getFirstItem();
        return $item->getId() ? (float)$item->getPrice() : null;
    }

    public function getPricesByProductIds(array $productIds, int $regionId): array
    {
        if (empty($productIds)) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('sv_product_region_price');

        $select = $connection->select()
            ->from($table, ['product_id', 'price'])
            ->where('product_id IN (?)', $productIds)
            ->where('region_id = ?', $regionId);

        $result = [];
        foreach ($connection->fetchAll($select) as $row) {
            $result[(int)$row['product_id']] = (float)$row['price'];
        }
        return $result;
    }

    public function replaceForProduct(int $productId, array $prices): void
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('sv_product_region_price');

        $this->validatePrices($prices);

        try {
            $connection->beginTransaction();

            $existing = $this->getByProductId($productId);
            $existingMap = [];
            foreach ($existing as $item) {
                $existingMap[(int)$item->getRegionId()] = $item;
            }

            $submittedRegions = [];
            foreach ($prices as $priceData) {
                $regionId = (int)$priceData['region_id'];
                $price = (float)$priceData['price'];

                $submittedRegions[] = $regionId;

                if (isset($existingMap[$regionId])) {
                    $connection->update(
                        $table,
                        ['price' => $price, 'updated_at' => gmdate('Y-m-d H:i:s')],
                        ['entity_id = ?' => (int)$existingMap[$regionId]->getEntityId()]
                    );
                } else {
                    $connection->insert(
                        $table,
                        [
                            'product_id' => $productId,
                            'region_id' => $regionId,
                            'price' => $price,
                            'created_at' => gmdate('Y-m-d H:i:s'),
                            'updated_at' => gmdate('Y-m-d H:i:s'),
                        ]
                    );
                }
            }

            foreach ($existingMap as $regionId => $item) {
                if (!in_array($regionId, $submittedRegions)) {
                    $connection->delete(
                        $table,
                        ['entity_id = ?' => (int)$item->getEntityId()]
                    );
                }
            }

            $connection->commit();
            unset($this->byProductCache[$productId]);
        } catch (\Exception $e) {
            $connection->rollBack();
            throw new CouldNotSaveException(
                __('Unable to save regional prices: %1', $e->getMessage()),
                $e
            );
        }
    }

    private function validatePrices(array $prices): void
    {
        foreach ($prices as $i => $priceData) {
            if (!isset($priceData['region_id']) || !isset($priceData['price'])) {
                throw new \InvalidArgumentException(
                    __('Row %1: region_id and price are required.', $i)
                );
            }
            $regionId = (int)$priceData['region_id'];
            $price = (float)$priceData['price'];

            if ($regionId <= 0) {
                throw new \InvalidArgumentException(
                    __('Row %1: Invalid region_id.', $i)
                );
            }
            if ($price < 0) {
                throw new \InvalidArgumentException(
                    __('Row %1: Price must be >= 0.', $i)
                );
            }

            try {
                $region = $this->regionRepository->getById($regionId);
                if ((int)$region->getStatus() === 0) {
                    throw new \InvalidArgumentException(
                        __('Row %1: Disabled regions cannot be used.', $i)
                    );
                }
            } catch (NoSuchEntityException $e) {
                throw new \InvalidArgumentException(
                    __('Row %1: Region does not exist.', $i)
                );
            }
        }

        $regionIds = array_map(fn($p) => (int)$p['region_id'], $prices);
        if (count($regionIds) !== count(array_unique($regionIds))) {
            throw new \InvalidArgumentException(
                __('Duplicate region assignment for the same product.')
            );
        }
    }
}
