<?php

declare(strict_types=1);

namespace SolarRelay\Relay;

/**
 * Dry-run/simulation (no real charger, just logs)
 */
readonly class DryRun extends AbstractRelay
{
    public function enableCharging(): void
    {
        $this->logger->info('DRY RUN ▶ Charging would be ENABLED now.');
    }

    public function disableCharging(): void
    {
        $this->logger->info('DRY RUN ■ Charging would be DISABLED now.');
    }
}
