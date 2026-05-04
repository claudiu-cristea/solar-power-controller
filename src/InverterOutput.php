<?php

declare(strict_types=1);

namespace SolarRelay;

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
