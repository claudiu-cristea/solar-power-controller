<?php

declare(strict_types=1);

namespace SolarRelay\Relay;

use Psr\Log\LoggerInterface;

interface RelayInterface
{
    public function enableCharging(): void;
    public function disableCharging(): void;
    public function setLogger(LoggerInterface $logger): static;
}
