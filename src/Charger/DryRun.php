<?php

declare(strict_types=1);

namespace SolarPowerController\Charger;

/**
 * Dry-run / simulation (no real charger, just logs)
 */
readonly class DryRun extends AbstractCharger
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
