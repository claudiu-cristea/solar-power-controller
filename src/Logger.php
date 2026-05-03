<?php

declare(strict_types=1);

namespace SolarPowerController;

use Monolog\JsonSerializableDateTimeImmutable;
use Monolog\Level;
use Monolog\Logger as MonologLogger;

class Logger extends MonologLogger
{
    public function addRecord(int|Level $level, string $message, array $context = [], JsonSerializableDateTimeImmutable|null $datetime = null): bool
    {
        $time = date('H:i:s');
        $padded = str_pad($level, 8);
        echo "{$time}  {$padded}  {$message}" . PHP_EOL;

    }
}
