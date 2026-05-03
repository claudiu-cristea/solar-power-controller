<?php

declare(strict_types=1);

namespace SolarPowerController\Charger;

/**
 * go-e Charger (v2 HTTP API)
 */
readonly class GoE extends AbstractCharger
{
    public function __construct(private string $host) {}

    private function set(bool $allow): void
    {
        $val = $allow ? '1' : '0';
        file_get_contents("http://{$this->host}/mqtt?payload=alw%3D{$val}");
        $this->logger->info('go-e charger: charging ' . ($allow ? 'ENABLED' : 'DISABLED'));
    }

    public function enableCharging(): void
    {
        $this->set(true);
    }

    public function disableCharging(): void
    {
        $this->set(false);
    }

}
