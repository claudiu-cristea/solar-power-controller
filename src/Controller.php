<?php

declare(strict_types=1);

namespace SolarPowerController;

use Psr\Log\LoggerInterface;

class Controller
{
    private bool $charging = false;

    public function run(): void
    {
        $this->logger->info('Solar EV controller started. Poll every '
          . $this->config['poll_interval_s'] . 's.');
        $this->logger->info(sprintf(
          'Thresholds — start: %d W  stop: %d W',
          $this->config['start_threshold_w'],
          $this->config['stop_threshold_w']
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
            sleep($this->config['poll_interval_s']);
        }
    }

    public function __construct(
      private readonly ChargerInterface     $charger,
      private readonly array                $config,
      private readonly InverterApiInterface $api,
      private readonly LoggerInterface      $logger,
    ) {}

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

        if (!$this->charging && $power >= $this->config['start_threshold_w']) {
            $this->logger->info(sprintf(
              'Production (%d W) >= start threshold (%d W) → ENABLING charging',
              $power, $this->config['start_threshold_w']
            ));
            $this->charger->enableCharging();
            $this->charging = true;

        } elseif ($this->charging && $power < $this->config['stop_threshold_w']) {
            $this->logger->info(sprintf(
              'Production (%d W) < stop threshold (%d W) → DISABLING charging',
              $power, $this->config['stop_threshold_w']
            ));
            $this->charger->disableCharging();
            $this->charging = false;
        }
    }

    private function shutdown(): void
    {
        $this->charger->disableCharging();
    }
}
