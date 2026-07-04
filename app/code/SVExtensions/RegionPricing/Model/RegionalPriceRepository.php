<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use SVExtensions\RegionPricing\Api\Data\RegionalPriceInterface;
use SVExtensions\RegionPricing\Api\Data\RegionalPriceSearchResultsInterface;
use SVExtensions\RegionPricing\Api\Data\RegionalPriceSearchResultsInterfaceFactory;
use SVExtensions\RegionPricing\Api\RegionalPriceRepositoryInterface;
use SVExtensions\RegionPricing\Model\ResourceModel\RegionalPrice as RegionalPriceResource;
use SVExtensions\RegionPricing\Model\ResourceModel\RegionalPrice\CollectionFactory;

class RegionalPriceRepository implements RegionalPriceRepositoryInterface
{
    public function __construct(
        private readonly RegionalPriceFactory $priceFactory,
        private readonly RegionalPriceResource $resource,
        private readonly CollectionFactory $collectionFactory,
        private readonly RegionalPriceSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor
    ) {
    }

    public function save(RegionalPriceInterface $price): RegionalPriceInterface
    {
        try {
            $this->resource->save($price);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __('Unable to save regional price.'),
                $e
            );
        }
        return $price;
    }

    public function getById(int $entityId): RegionalPriceInterface
    {
        $price = $this->priceFactory->create();
        $this->resource->load($price, $entityId);
        if (!$price->getId()) {
            throw new NoSuchEntityException(
                __('Regional price does not exist.')
            );
        }
        return $price;
    }

    public function getByProductAndRegion(int $productId, int $regionId): ?RegionalPriceInterface
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(RegionalPriceInterface::PRODUCT_ID, $productId);
        $collection->addFieldToFilter(RegionalPriceInterface::REGION_ID, $regionId);
        $collection->setPageSize(1);
        $item = $collection->getFirstItem();
        return $item->getId() ? $item : null;
    }

    public function getPriceByProductAndRegion(int $productId, int $regionId): ?float
    {
        $price = $this->getByProductAndRegion($productId, $regionId);
        return $price ? $price->getPrice() : null;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): RegionalPriceSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    public function delete(RegionalPriceInterface $price): bool
    {
        try {
            $this->resource->delete($price);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(
                __('Unable to delete regional price.'),
                $e
            );
        }
        return true;
    }

    public function deleteById(int $entityId): bool
    {
        return $this->delete($this->getById($entityId));
    }
}
