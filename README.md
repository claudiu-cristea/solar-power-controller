[![PHPCS](https://github.com/claudiu-cristea/solar-power-controller/actions/workflows/phpcs.yml/badge.svg)](https://github.com/claudiu-cristea/solar-power-controller/actions/workflows/phpcs.yml)

Reads the live power from an inverter that supports Modbus/TCP API and turns ON
or OFF a list of configured chargers, depending on whether the measured produced
power is between the configured thresholds.

## Installation and configuration

Use composer to install the package:

```bash
composer require claudiu-cristea/solar-power-controller
```

Create a config file by copying the `example.config.yml` file as `config.yml` 
and edit it to your needs.

## Running

Run the `bin/solar-power-controller` command.
