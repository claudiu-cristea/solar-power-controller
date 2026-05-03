<?php
/**
 * solar_ev_charger.php
 * ─────────────────────────────────────────────────────────────────────────────
 * Controls EV charging based on solar production from a Huawei SUN2000-10KTL-M1.
 * Reads live power from the inverter via Modbus TCP (SDongle on your LAN).
 *
 * Requirements:
 *   PHP 8.1+ CLI (php-cli), with sockets extension enabled (usually default).
 *   No Composer packages needed — Modbus TCP is implemented with raw sockets.
 *
 * Usage:
 *   php solar_ev_charger.php
 *
 * Configuration:
 *   Edit the CONFIG block below before running.
 * ─────────────────────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use SolarPowerController\ModbusTcpApi;
use SolarPowerController\Controller;
use Symfony\Component\Yaml\Yaml;


// ── LOGGING ──────────────────────────────────────────────────────────────────

function log_msg(string $level, string $message): void
{
    $time = date('H:i:s');
    $padded = str_pad($level, 8);
    echo "{$time}  {$padded}  {$message}" . PHP_EOL;
}

function log_info(string $msg): void  { log_msg('INFO',    $msg); }
function log_warn(string $msg): void  { log_msg('WARNING', $msg); }
function log_error(string $msg): void { log_msg('ERROR',   $msg); }


// ── ENTRY POINT ───────────────────────────────────────────────────────────────

require_once __DIR__ . '/vendor/autoload.php';

$formatter = new LineFormatter(
  format: "%datetime% %level_name%: %message%\n",
  dateFormat: 'Y-m-d H:i'
);
$pushHandler = new StreamHandler('php://stdout', Level::Info)->setFormatter($formatter);
$logger = new Logger('solar_power_controller')
    ->pushHandler($pushHandler)
    ->pushHandler(new \Monolog\Handler\SyslogHandler(
      ident: 'solar_power_controller',
      level: Level::Warning,
    ));

$config = Yaml::parse(file_get_contents(__DIR__ . '/config.yml'));
$chargerConfig = $config['chargers'][$config['charger']] ?? [];
$charger = new $config['charger'](...$chargerConfig)->setLogger($logger);

$api = new ModbusTcpApi(
  logger: $logger,
  host: $config['inverter_host'],
  port: $config['inverter_port'],
  unit: $config['inverter_unit_id'],
  postConnectDelay: $config['post_connect_delay_s'],
);

$controller = new Controller(
  charger: $charger,
  config: $config,
  api: $api,
  logger: $logger,
);

$controller->run();
