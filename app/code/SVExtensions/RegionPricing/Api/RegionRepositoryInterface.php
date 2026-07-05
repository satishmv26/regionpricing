<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use SVExtensions\RegionPricing\Api\Data\RegionInterface;
use SVExtensions\RegionPricing\Api\Data\RegionSearchResultsInterface;

/**
 * Region Repository Interface
 */
interface RegionRepositoryInterface
{
    /**
     * Save Region
     *
     * @param RegionInterface $region
     * @return RegionInterface
     * @throws CouldNotSaveException
     */
    public function save(
        RegionInterface $region
    ): RegionInterface;

    /**
     * Get Region By Id
     *
     * @param int $regionId
     * @return RegionInterface
     * @throws NoSuchEntityException
     */
    public function getById(
        int $regionId
    ): RegionInterface;

    /**
     * Get Region By Code
     *
     * Required for Region Resolver
     *
     * @param string $code
     * @return RegionInterface
     * @throws NoSuchEntityException
     */
    public function getByCode(
        string $code
    ): RegionInterface;

    /**
     * Get Region List
     *
     * @param SearchCriteriaInterface|null $searchCriteria
     * @return RegionSearchResultsInterface
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria = null
    ): RegionSearchResultsInterface;

    /**
     * Delete Region
     *
     * @param RegionInterface $region
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(
        RegionInterface $region
    ): bool;

    /**
     * Delete Region By Id
     *
     * @param int $regionId
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function deleteById(
        int $regionId
    ): bool;
}