<?php

declare(strict_types=1);

namespace SolarPowerController\Charger;

use Psr\Log\LoggerInterface;

readonly abstract class AbstractCharger implements ChargerInterface
{
    protected LoggerInterface $logger;

    public function setLogger(LoggerInterface $logger): static
    {
        $this->logger = $logger;
        return $this;
    }
}
