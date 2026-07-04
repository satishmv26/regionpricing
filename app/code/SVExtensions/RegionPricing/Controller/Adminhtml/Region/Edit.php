<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Controller\Adminhtml\Region;

use Magento\Framework\Exception\NoSuchEntityException;

class Edit extends AbstractRegion
{
    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('region_id');

        if ($id) {
            try {
                $this->regionRepository->getById($id);
            } catch (NoSuchEntityException $exception) {
                $this->messageManager->addErrorMessage(
                    __('This region no longer exists.')
                );

                return $this->_redirect('*/*/');
            }
        }

        $resultPage = $this->resultPageFactory->create();

        $resultPage->setActiveMenu(
            'SVExtensions_RegionPricing::regions'
        );

        $resultPage->getConfig()->getTitle()->prepend(
            $id ? __('Edit Region') : __('New Region')
        );

        return $resultPage;
    }
}