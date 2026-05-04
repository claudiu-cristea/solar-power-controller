<?php

declare(strict_types=1);

namespace SolarPowerController\Charger;

use Psr\Log\LoggerInterface;

interface ChargerInterface
{
    public function enableCharging(): void;
    public function disableCharging(): void;
    public function setLogger(LoggerInterface $logger): static;
}
