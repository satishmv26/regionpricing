<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Controller\Adminhtml\Region;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use SVExtensions\RegionPricing\Api\Data\RegionInterface;
use SVExtensions\RegionPricing\Model\RegionFactory;

class Save extends AbstractRegion implements HttpPostActionInterface
{
    private const DATA_PERSISTOR_KEY = 'sv_region';

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \SVExtensions\RegionPricing\Api\RegionRepositoryInterface $regionRepository,
        private readonly RegionFactory $regionFactory,
        private readonly DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context, $resultPageFactory, $regionRepository);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = (array)$this->getRequest()->getPostValue();

        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        $regionId = (int)($data[RegionInterface::REGION_ID] ?? 0);

        try {
            $region = $regionId
                ? $this->regionRepository->getById($regionId)
                : $this->regionFactory->create();

            $this->validate($data);

            $region->setData(RegionInterface::NAME, trim((string)$data[RegionInterface::NAME]));
            $region->setData(RegionInterface::CODE, strtoupper(trim((string)$data[RegionInterface::CODE])));
            $region->setData(
                RegionInterface::CURRENCY_CODE,
                strtoupper(trim((string)$data[RegionInterface::CURRENCY_CODE]))
            );
            $region->setData(RegionInterface::STATUS, (int)$data[RegionInterface::STATUS]);

            $this->regionRepository->save($region);
            $this->messageManager->addSuccessMessage(__('You saved the region.'));
            $this->dataPersistor->clear(self::DATA_PERSISTOR_KEY);

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['region_id' => $region->getRegionId()]);
            }

            return $resultRedirect->setPath('*/*/');
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Throwable $exception) {
            $this->messageManager->addExceptionMessage($exception, __('Something went wrong while saving the region.'));
        }

        $this->dataPersistor->set(self::DATA_PERSISTOR_KEY, $data);

        return $resultRedirect->setPath('*/*/edit', ['region_id' => $regionId]);
    }

    private function validate(array $data): void
    {
        foreach ([RegionInterface::NAME, RegionInterface::CODE, RegionInterface::CURRENCY_CODE] as $field) {
            if (trim((string)($data[$field] ?? '')) === '') {
                throw new LocalizedException(__('Please complete all required fields.'));
            }
        }

        if (strlen(trim((string)$data[RegionInterface::CURRENCY_CODE])) !== 3) {
            throw new LocalizedException(__('Currency code must be a 3 character ISO code.'));
        }
    }
}
