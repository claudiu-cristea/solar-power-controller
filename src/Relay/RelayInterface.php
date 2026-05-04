<?php

declare(strict_types=1);

namespace SolarRelay\Relay;

interface RelayInterface
{
    public function enableCharging(): void;
    public function disableCharging(): void;
}
