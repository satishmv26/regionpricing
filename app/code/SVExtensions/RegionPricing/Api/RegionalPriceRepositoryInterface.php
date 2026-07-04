<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use SVExtensions\RegionPricing\Api\Data\RegionalPriceInterface;
use SVExtensions\RegionPricing\Api\Data\RegionalPriceSearchResultsInterface;

interface RegionalPriceRepositoryInterface
{
    /**
     * @param RegionalPriceInterface $price
     * @return RegionalPriceInterface
     * @throws CouldNotSaveException
     */
    public function save(RegionalPriceInterface $price): RegionalPriceInterface;

    /**
     * @param int $entityId
     * @return RegionalPriceInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): RegionalPriceInterface;

    /**
     * @param int $productId
     * @param int $regionId
     * @return RegionalPriceInterface|null
     */
    public function getByProductAndRegion(int $productId, int $regionId): ?RegionalPriceInterface;

    /**
     * @param int $productId
     * @param int $regionId
     * @return float|null
     */
    public function getPriceByProductAndRegion(int $productId, int $regionId): ?float;

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return RegionalPriceSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): RegionalPriceSearchResultsInterface;

    /**
     * @param RegionalPriceInterface $price
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(RegionalPriceInterface $price): bool;

    /**
     * @param int $entityId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $entityId): bool;
}
