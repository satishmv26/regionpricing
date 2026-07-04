<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Controller\Adminhtml\Region;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use SVExtensions\RegionPricing\Api\RegionRepositoryInterface;

abstract class AbstractRegion extends Action
{
    /**
     * ACL Resource
     */
    public const ADMIN_RESOURCE = 'SVExtensions_RegionPricing::regions';

    public function __construct(
        Action\Context $context,
        protected readonly PageFactory $resultPageFactory,
        protected readonly RegionRepositoryInterface $regionRepository
    ) {
        parent::__construct($context);
    }
}