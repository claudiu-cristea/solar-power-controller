<?php

declare(strict_types=1);

namespace SolarRelay;

use Symfony\Component\Yaml\Yaml;

readonly final class Config
{
    public function __construct(private array $config)
    {
    }

    public static function fromFile(string $file): self
    {
        return new self(Yaml::parse(file_get_contents($file)));
    }

    public function getInverterHost(): string
    {
        return $this->config['inverter']['host'] ?? throw new \RuntimeException('No inverter host configured.');
    }

    public function getInverterPort(): int
    {
        return $this->config['inverter']['port'] ?? throw new \RuntimeException('No inverter port configured.');
    }

    public function getUnitId(): int
    {
        return $this->config['inverter']['unit_id'] ?? throw new \RuntimeException('No unit ID configured.');
    }

    public function getStartThreshold(): int
    {
        return $this->config['charging_threshold']['start']
          ?? throw new \RuntimeException('No start threshold configured.');
    }

    public function getStopThreshold(): int
    {
        return $this->config['charging_threshold']['stop']
          ?? throw new \RuntimeException('No stop threshold configured.');
    }

    public function getPollInterval(): int
    {
        return $this->config['interval']['poll'] ?? throw new \RuntimeException('No interval poll configured.');
    }

    public function getShutdownDelay(): int
    {
        return $this->config['shutdown_delay'] ?? throw new \RuntimeException('No shutdown delay configured.');
    }

    public function getDefaultLoggerStream(): string
    {
        return $this->config['logger']['stream']['default'] ?? 'php://stdout';
    }

    public function getErrorLoggerStream(): string
    {
        return $this->config['logger']['stream']['error'] ?? 'php://stderr';
    }

    public function getRelays(): array
    {
        return $this->config['relays'] ?? [];
    }

    public function getApiClass(): string
    {
        return $this->config['api']['class'] ?? throw new \RuntimeException('No API class configured.');
    }

    public function getApiParams(): array
    {
        return $this->config['api']['params'] ?? [];
    }
}
