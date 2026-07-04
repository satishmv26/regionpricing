<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Model;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use SVExtensions\RegionPricing\Api\Data\RegionInterface;
use SVExtensions\RegionPricing\Api\RegionRepositoryInterface;
use SVExtensions\RegionPricing\Helper\Logger;
use Magento\Framework\App\Http\Context;

class RegionProvider
{
    public const ATTRIBUTE_CODE = 'sv_region_id';
    public const CONTEXT_REGION_ID = 'sv_region_id';

    private ?RegionInterface $currentRegion = null;

    private bool $regionResolved = false;

    private ?int $currentRegionId = null;

    private bool $regionIdResolved = false;

    public function __construct(
        private readonly Config $config,
        private readonly RegionRepositoryInterface $regionRepository,
        private readonly CustomerSession $customerSession,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly Logger $logger,
        private readonly ResourceConnection $resourceConnection,
        private readonly EavConfig $eavConfig,
        private readonly Context $httpContext,
    ) {
    }

    /**
     * Get current active Region entity.
     */
    public function getCurrentRegion(): ?RegionInterface
    {
        if ($this->regionResolved) {
            return $this->currentRegion;
        }

        $this->regionResolved = true;

        if (!$this->config->isEnabled()) {
            return null;
        }

        $regionId = $this->getCurrentRegionId();

        if ($regionId === null) {
            return null;
        }

        try {
            $region = $this->regionRepository->getById($regionId);

            if (
                (int)$region->getStatus()
                !== RegionInterface::STATUS_ENABLED
            ) {
                $this->logger->warning(
                    'Customer Region is disabled.',
                    [
                        'region_id' => $regionId
                    ]
                );

                return null;
            }

            $this->currentRegion = $region;

            return $this->currentRegion;
        } catch (NoSuchEntityException $exception) {
            $this->logger->warning(
                'Customer Region does not exist.',
                [
                    'region_id' => $regionId,
                    'error' => $exception->getMessage()
                ]
            );

            return null;
        } catch (\Throwable $exception) {
            $this->logger->warning(
                'Region resolution failed.',
                [
                    'region_id' => $regionId,
                    'error' => $exception->getMessage()
                ]
            );

            return null;
        }
    }

    /**
     * Get Region ID assigned to logged-in customer.
     */
   public function getCurrentRegionId(): ?int
    {
        if (!$this->config->isEnabled()) {
            return null;
        }

        $regionId = (int)$this->httpContext->getValue(
            self::CONTEXT_REGION_ID
        );

        return $regionId > 0 ? $regionId : null;
    }

    /**
     * Get current Region Code.
     */
    public function getCurrentRegionCode(): ?string
    {
        $region = $this->getCurrentRegion();

        return $region !== null
            ? (string)$region->getCode()
            : null;
    }

    /**
     * Get all active Regions.
     *
     * @return RegionInterface[]
     */
    public function getActiveRegions(): array
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $regions = $this->regionRepository
            ->getList($searchCriteria)
            ->getItems();

        return array_values(
            array_filter(
                $regions,
                static function (RegionInterface $region): bool {
                    return (int)$region->getStatus()
                        === RegionInterface::STATUS_ENABLED;
                }
            )
        );
    }
}