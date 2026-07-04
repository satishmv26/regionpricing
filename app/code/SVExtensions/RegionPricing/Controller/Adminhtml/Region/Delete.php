<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Controller\Adminhtml\Region;

class Delete extends AbstractRegion
{
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $regionId = (int)$this->getRequest()->getParam('region_id');

        if (!$regionId) {
            $this->messageManager->addErrorMessage(__('We cannot find a region to delete.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $this->regionRepository->deleteById($regionId);
            $this->messageManager->addSuccessMessage(__('You deleted the region.'));
        } catch (\Throwable $exception) {
            $this->messageManager->addExceptionMessage($exception, __('Something went wrong while deleting the region.'));
        }

        return $resultRedirect->setPath('*/*/');
    }
}
