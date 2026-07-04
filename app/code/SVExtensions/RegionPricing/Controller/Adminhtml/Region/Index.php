<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Controller\Adminhtml\Region;

class Index extends AbstractRegion
{
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('SVExtensions_RegionPricing::regions');
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Regions'));

        return $resultPage;
    }
}
