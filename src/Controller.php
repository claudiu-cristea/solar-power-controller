<?php

declare(strict_types=1);

namespace SolarRelay;

use Psr\Log\LoggerInterface;
use SolarRelay\Api\ApiInterface;

class Controller
{
    private bool $charging = false;
    private array $relays;

    public function __construct(
        private readonly Config $config,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function run(): void
    {
        $this->logger->info("Solar EV controller started. Poll every {$this->config->getPollInterval()}s.");
        $this->logger->info(sprintf(
            'Thresholds — start: %d W  stop: %d W',
            $this->config->getStartThreshold(),
            $this->config->getStopThreshold(),
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
            sleep($this->config->getPollInterval());
        }
    }

    private function update(): void
    {
        $data = $this->getApi()->getPower();

        if ($data === null) {
            $this->logger->warning('Could not read inverter — keeping current charger state.');
            return;
        }

        $state = $this->charging ? 'ON' : 'OFF';

        $this->logger->info("$data->power W, Status: $data->status, Charger: $state");

        if (!$this->charging && $data->power >= $this->config->getStartThreshold()) {
            $this->logger->info(
                sprintf(
                    'Production (%d W) >= start threshold (%d W) → ENABLING charging',
                    $data->power,
                    $this->config->getStartThreshold(),
                )
            );
            foreach ($this->getRelays() as $charger) {
                $charger->enableCharging();
            }
            $this->charging = true;
        } elseif ($this->charging && $data->power < $this->config->getStopThreshold()) {
            $this->logger->info(sprintf(
                'Production (%d W) < stop threshold (%d W) → DISABLING charging',
                $data->power,
                $this->config->getStopThreshold(),
            ));
            foreach ($this->getRelays() as $charger) {
                $charger->disableCharging();
            }
            $this->charging = false;
        }
    }

    private function shutdown(): void
    {
        foreach ($this->getRelays() as $charger) {
            $charger->disableCharging();
        }
    }

    public function getApi(): ApiInterface
    {
        $class = $this->config->getApiClass();
        $params = $this->config->getApiParams() + [
            'config' => $this->config,
            'logger' => $this->logger,
        ];
        return new $class(...$params);
    }

    /**
     * @return \SolarRelay\Relay\RelayInterface[]
     */
    public function getRelays(): array
    {
        if (!isset($this->relays)) {
            $this->relays = [];
            foreach ($this->config->getRelays() as $class => $params) {
                $params ??= [];
                if (!empty($params) && array_is_list($params)) {
                    throw new \RuntimeException("Relay $class parameters must be an associative array.");
                }
                $params += ['logger' => $this->logger];
                $this->relays[] = new $class(...$params);
            }
        }
        return $this->relays;
    }
}
