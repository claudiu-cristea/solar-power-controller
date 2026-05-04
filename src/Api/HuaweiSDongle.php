<?php

declare(strict_types=1);

namespace SolarRelay\Api;

use Psr\Log\LoggerInterface;
use SolarRelay\Config;

readonly class HuaweiSDongle extends ModbusTcpApi
{
    public function __construct(
        Config $config,
        LoggerInterface $logger,
        protected float $postConnectDelay,
    ) {
        parent::__construct($config, $logger);
    }

    protected function postConnect($socket): void
    {
        // Huawei SDongle drops the connection if you write too soon after connect.
        if ($this->postConnectDelay > 0) {
            usleep((int) ($this->postConnectDelay * 1_000_000));
        }
    }
}
