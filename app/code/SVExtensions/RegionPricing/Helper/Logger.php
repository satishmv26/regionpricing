<?php
declare(strict_types=1);

namespace SVExtensions\RegionPricing\Helper;

use Psr\Log\LoggerInterface;
use SVExtensions\RegionPricing\Model\Config;

class Logger
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Config $config
    ) {
    }

    public function info(string $message, array $context = []): void
    {
        if ($this->config->isLoggingEnabled()) {
            $this->logger->info('[SVRegionPricing] ' . $message, $context);
        }
    }

    public function warning(string $message, array $context = []): void
    {
        if ($this->config->isLoggingEnabled()) {
            $this->logger->warning('[SVRegionPricing] ' . $message, $context);
        }
    }

    public function error(string $message, array $context = []): void
    {
        $this->logger->error('[SVRegionPricing] ' . $message, $context);
    }

    public function audit(string $action, array $data = []): void
    {
        if ($this->config->isLoggingEnabled()) {
            $this->logger->info('[SVRegionPricing][AUDIT] ' . $action, $data);
        }
    }
}
