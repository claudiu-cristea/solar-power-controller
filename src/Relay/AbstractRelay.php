<?php

declare(strict_types=1);

namespace SolarRelay\Relay;

use Psr\Log\LoggerInterface;

readonly abstract class AbstractRelay implements RelayInterface
{
    public function __construct(
        protected LoggerInterface $logger,
    ) {
    }
}
