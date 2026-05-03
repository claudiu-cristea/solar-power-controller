<?php

declare(strict_types=1);

namespace SolarPowerController;

interface InverterApiInterface
{

    public function readInverter(): ?array;

}
