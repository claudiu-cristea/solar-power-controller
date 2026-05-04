[![PHPCS](https://github.com/claudiu-cristea/solar-relay/actions/workflows/ci.yml/badge.svg)](https://github.com/claudiu-cristea/solar-relay/actions/workflows/ci.yml)

Reads the live power from an inverter that supports Modbus/TCP API and turns ON
or OFF a list of configured relays, depending on whether the measured produced
power is between the configured thresholds.

## Installation and configuration

Use composer to install the package:

```bash
composer require claudiu-cristea/solar-relay
```

Create a config file by copying the `example.config.yml` file as `config.yml` 
and edit it to your needs.

## Running

Run the `bin/solar-relay` command.
