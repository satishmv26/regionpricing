<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Plugin\Customer\Controller\Account;

use Magento\Customer\Controller\Account\CreatePost;
use Magento\Customer\Controller\Account\EditPost;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use SVExtensions\RegionPricing\Api\RegionRepositoryInterface;
use SVExtensions\RegionPricing\Api\Data\RegionInterface;
use SVExtensions\RegionPricing\Model\Config;

class RegionValidationPlugin
{
    private const FIELD_NAME = 'sv_region_id';

    public function __construct(
        private readonly RequestInterface $request,
        private readonly RedirectFactory $redirectFactory,
        private readonly ManagerInterface $messageManager,
        private readonly RegionRepositoryInterface $regionRepository,
        private readonly Config $config
    ) {
    }

    public function aroundExecute($subject, callable $proceed)
    {
        // When module is disabled, skip validation entirely
        if (!$this->config->isEnabled()) {
            return $proceed();
        }
        return $this->doAroundExecute($subject, $proceed);
    }

    private function doAroundExecute($subject, callable $proceed)
    {
        if ($subject instanceof CreatePost) {
            return $this->aroundCreatePostExecute($proceed);
        }
        if ($subject instanceof EditPost) {
            return $this->aroundEditPostExecute($proceed);
        }
        return $proceed();
    }

    private function aroundCreatePostExecute(callable $proceed)
    {
        $regionId = $this->request->getParam(self::FIELD_NAME);
        if ($regionId === null || $regionId === '') {
            $this->messageManager->addErrorMessage(
                __('Please select a Pricing Region.')
            );
            return $this->redirectFactory->create()->setPath('customer/account/create');
        }

        $error = $this->validateRegion((int)$regionId);
        if ($error) {
            $this->messageManager->addErrorMessage($error);
            return $this->redirectFactory->create()->setPath('customer/account/create');
        }

        return $proceed();
    }

    private function aroundEditPostExecute(callable $proceed)
    {
        $regionId = $this->request->getParam(self::FIELD_NAME);
        if ($regionId === null || $regionId === '') {
            return $proceed();
        }

        $error = $this->validateRegion((int)$regionId);
        if ($error) {
            $this->messageManager->addErrorMessage($error);
            return $this->redirectFactory->create()->setPath('customer/account/edit');
        }

        return $proceed();
    }

    private function validateRegion(int $regionId): ?Phrase
    {
        try {
            $region = $this->regionRepository->getById($regionId);
            if ((int)$region->getStatus() !== RegionInterface::STATUS_ENABLED) {
                return __('The selected Pricing Region is not available.');
            }
        } catch (NoSuchEntityException $e) {
            return __('The selected Pricing Region is invalid.');
        }
        return null;
    }
}
