<?php

declare(strict_types=1);

namespace SolarRelay\Api;

use Psr\Log\LoggerInterface;
use SolarRelay\Config;
use SolarRelay\InverterOutput;

/**
 * Implements just enough of the Modbus TCP spec to read holding registers.
 *
 * Frame format: [Transaction ID 2B][Protocol ID 2B][Length 2B][Unit ID 1B]
 *               [Function Code 1B][Start Addr 2B][Quantity 2B]
 */
readonly class ModbusTcpApi implements ApiInterface
{
    private const array STATUS_CODES = [
      0x0000 => 'Standby: initialising',
      0x0001 => 'Standby: detecting insulation resistance',
      0x0002 => 'Standby: detecting irradiation',
      0x0003 => 'Standby: grid detecting',
      0x0100 => 'Starting',
      0x0200 => 'On-grid (generating)',
      0x0201 => 'On-grid: power limited',
      0x0202 => 'On-grid: self-derating',
      0x0300 => 'Shutdown: fault',
      0x0301 => 'Shutdown: command',
      0x0302 => 'Shutdown: OVGR',
      0x0303 => 'Shutdown: communication disconnected',
      0x0304 => 'Shutdown: power limited',
      0x0305 => 'Shutdown: manual startup required',
      0x0306 => 'Shutdown: DC switches disconnected',
      0x0401 => 'Grid scheduling: cosφ-P curve',
      0x0501 => 'Spot-check ready',
      0x0502 => 'Spot-checking',
      0x0600 => 'Inspecting',
      0x0700 => 'AFCI self check',
      0x0800 => 'I-V scanning',
      0x0900 => 'DC input detection',
      0x0a00 => 'Running: off-grid charging',
      0x1500 => 'Standby: no irradiation',
    ];

    public function __construct(
        protected Config $config,
        protected LoggerInterface $logger,
    ) {
    }

    public function getPower(): ?InverterOutput
    {
        // Read 32080..32089 in one shot: power (2 regs) + 7 unused + status (1
        // reg). Huawei allows only one Modbus client at a time, so we minimize
        // round-trips.
        $regs = $this->readHoldingRegisters(
            // TBD
            32080,
            10,
        );
        if ($regs === false || count($regs) < 10) {
            return null;
        }

        // Combine two 16-bit registers into a signed 32-bit integer
        $raw = ($regs[0] << 16) | $regs[1];
        if ($raw >= 0x80000000) {
            // Two's complement for negative values.
            $raw -= 0x100000000;
        }
        $power = $raw;

        $statusCode = $regs[9];
        $statusText = self::STATUS_CODES[$statusCode] ?? sprintf(
            'Unknown (0x%04X)',
            $statusCode
        );

        return new InverterOutput(power: $power, status: $statusText);
    }

    protected function readHoldingRegisters(
        // TBD
        int $start_register,
        int $count,
        int $timeout_s = 5,
    ): array|false {
        $socket = $this->connect($timeout_s);
        $this->postConnect($socket);

        // MBAP header = TxID(2) + ProtoID(2) + Length(2) + UnitID(1)
        // PDU         = FC(1) + StartAddr(2) + Qty(2)
        $transaction_id = 0x0001;
        $protocol_id = 0x0000;
        $function_code = 0x03;   // Read Holding Registers

        $request = pack('nnn', $transaction_id, $protocol_id, 6)
          . pack('CCnn', $this->config->getUnitId(), $function_code, $start_register, $count);

        fwrite($socket, $request);

        // Read response: minimum 9 bytes (MBAP 7 + FC 1 + byte_count 1).
        // fread() can return short reads on TCP, so loop until we have all 9.
        $header = $this->readExact($socket, 9);
        if ($header === false) {
            $this->logger->error('Modbus: incomplete response header');
            fclose($socket);
            return false;
        }

        $unpacked = unpack(
            'ntx_id/nproto/nlength/Cunit/Cfc/Cbyte_count',
            $header
        );
        $byte_count = $unpacked['byte_count'];
        $fc = $unpacked['fc'];

        // FC >= 0x80 signals a Modbus exception
        if ($fc >= 0x80) {
            $exception_code = $byte_count; // byte_count position holds exception code here
            $this->logger->error("Modbus exception response, FC=0x" . sprintf('%02X', $fc)
              . ", code={$exception_code}");
            fclose($socket);
            return false;
        }

        $data = $this->readExact($socket, $byte_count);
        fclose($socket);

        if ($data === false) {
            $this->logger->error('Modbus: incomplete register data');
            return false;
        }

        // Unpack 16-bit unsigned words
        $registers = [];
        for ($i = 0; $i < $byte_count; $i += 2) {
            $registers[] = unpack('n', substr($data, $i, 2))[1];
        }

        return $registers;
    }

    protected function connect(int $timeout_s)
    {
        $socket = @fsockopen(
            $this->config->getInverterHost(),
            $this->config->getInverterPort(),
            $errno,
            $errstr,
            $timeout_s,
        );
        if (!$socket) {
            $this->logger->error(sprintf(
                'Cannot connect to %s:%d — %s (%d)',
                $this->config->getInverterHost(),
                $this->config->getInverterPort(),
                $errstr,
                $errno,
            ));
            return false;
        }
        stream_set_timeout($socket, $timeout_s);

        return $socket;
    }

    protected function postConnect($socket): void
    {
    }

    /**
     * Read exactly $n bytes from $socket, looping over short reads. Returns
     * false on timeout/EOF before $n bytes are available.
     */
    protected function readExact($socket, int $n): string|false
    {
        $buffer = '';
        while (strlen($buffer) < $n) {
            $chunk = fread($socket, $n - strlen($buffer));
            if ($chunk === false || $chunk === '') {
                return false;
            }
            $buffer .= $chunk;
        }
        return $buffer;
    }
}
