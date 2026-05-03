<?php

declare(strict_types=1);

namespace SolarPowerController\Charger;

/**
 * Generic HTTP charger (simple GET to start/stop URLs)
 */
readonly class GenericHttp extends AbstractCharger
{
    public function __construct(
        private string $enableUrl,
        private string $disableUrl,
    ) {
    }

    public function enableCharging(): void
    {
        file_get_contents($this->enableUrl);
        $this->logger->info('HTTP charger: charging ENABLED');
    }

    public function disableCharging(): void
    {
        file_get_contents($this->disableUrl);
        $this->logger->info('HTTP charger: charging DISABLED');
    }
}
