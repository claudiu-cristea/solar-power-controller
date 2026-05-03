<?php

declare(strict_types=1);

namespace SolarPowerController;

use Psr\Log\LoggerInterface;

class Controller
{
    private bool $charging = false;

    public function __construct(
        private readonly array $chargers,
        private readonly array $config,
        private readonly InverterApiInterface $api,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function run(): void
    {
        $this->logger->info('Solar EV controller started. Poll every '
          . $this->config['interval']['poll'] . 's.');
        $this->logger->info(sprintf(
            'Thresholds — start: %d W  stop: %d W',
            $this->config['charging_threshold']['start'],
            $this->config['charging_threshold']['stop'],
        ));

        // Register shutdown handler so Ctrl+C disables charging cleanly
        $controller = $this;
        pcntl_signal(SIGINT, function () use ($controller) {
            $this->logger->info('Stopped by user. Disabling charging as a safety measure.');
            $controller->shutdown();
            exit(0);
        });

        while (true) {
            pcntl_signal_dispatch();
            $this->update();
            sleep($this->config['interval']['poll']);
        }
    }

    private function update(): void
    {
        $data = $this->api->readInverter();

        if ($data === null) {
            $this->logger->warning('Could not read inverter — keeping current charger state.');
            return;
        }

        $power       = $data['power_w'];
        $status      = $data['status_text'];
        $chargerState = $this->charging ? 'ON' : 'OFF';

        $this->logger->info(sprintf('%d W, Status: %s, Charger: %s', $power, $status, $chargerState));

        if (!$this->charging && $power >= $this->config['charging_threshold']['start']) {
            $this->logger->info(sprintf(
                'Production (%d W) >= start threshold (%d W) → ENABLING charging',
                $power,
                $this->config['charging_threshold']['start'],
            ));
            foreach ($this->chargers as $charger) {
                $charger->enableCharging();
            }
            $this->charging = true;
        } elseif ($this->charging && $power < $this->config['charging_threshold']['stop']) {
            $this->logger->info(sprintf(
                'Production (%d W) < stop threshold (%d W) → DISABLING charging',
                $power,
                $this->config['charging_threshold']['stop']
            ));
            foreach ($this->chargers as $charger) {
                $charger->disableCharging();
            }
            $this->charging = false;
        }
    }

    private function shutdown(): void
    {
        foreach ($this->chargers as $charger) {
            $charger->disableCharging();
        }
    }
}
