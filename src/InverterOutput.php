<?php

declare(strict_types=1);

namespace SolarPowerController;

readonly class InverterOutput
{
    /**
     * @param int $power Power in Watt
     * @param string $status Status text
     */
    public function __construct(
        public int $power,
        public string $status,
    ) {
    }
}
