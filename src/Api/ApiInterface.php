<?php

declare(strict_types=1);

namespace SolarRelay\Api;

use SolarRelay\InverterOutput;

interface ApiInterface
{
    public function getPower(): ?InverterOutput;
}
