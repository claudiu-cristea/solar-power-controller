<?php

declare(strict_types=1);

namespace SolarRelay\Relay;

use Psr\Log\LoggerInterface;

readonly abstract class AbstractRelay implements RelayInterface
{
    protected LoggerInterface $logger;

    public function setLogger(LoggerInterface $logger): static
    {
        $this->logger = $logger;
        return $this;
    }
}
