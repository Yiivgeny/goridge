<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Goridge;

use Spiral\Goridge\Exception\RelayException;
use Spiral\Goridge\Exception\ServiceException;
use Spiral\Goridge\Exception\TransportException;
use Spiral\Goridge\Relay\Payload;
use Spiral\Goridge\RelayInterface as Relay;

/**
 * RPC bridge to Golang net/rpc package over Goridge protocol.
 */
class RPC
{
    /**
     * @var string
     */
    private const ERROR_SERVER_ERROR = 'The RPC server %s returned an error: %s';

    /**
     * @var string
     */
    private const ERROR_HEADER_MISSING = 'Header is missing in the RPC server %s response';

    /**
     * @var string
     */
    private const ERROR_MISMATCH_METHOD =
        'During a call method %s:%d on the RPC server %s, it ' .
        'returned an incorrect response indicating method %s:%d';

    /**
     * @var Relay
     */
    private $relay;

    /**
     * @var int
     */
    private $seq = 0;

    /**
     * @param Relay $relay
     */
    public function __construct(Relay $relay)
    {
        $this->relay = $relay;
    }

    /**
     * @param string $method
     * @param mixed  $payload An binary data or array of arguments for complex types.
     * @param int    $flags   Payload control flags.
     * @return mixed
     * @throws RelayException
     */
    public function call(string $method, $payload, int $flags = 0)
    {
        $this->send($method, $payload, $flags);

        [$body, $flags] = $this->relay->receive();

        if (!($flags & Payload::TYPE_CONTROL)) {
            throw new TransportException(\sprintf(self::ERROR_HEADER_MISSING, $this->getRelayAsString()));
        }

        $rpc = \unpack('Ps', \substr($body, -8));
        $rpc['m'] = \substr($body, 0, -8);

        if ($rpc['m'] !== $method || $rpc['s'] !== $this->seq) {
            $message = \vsprintf(self::ERROR_MISMATCH_METHOD, [
                $method, $this->seq,
                $this->getRelayAsString(),
                $rpc['m'], $rpc['s']
            ]);

            throw new TransportException($message);
        }

        // Request id++
        $this->seq++;

        // Wait for the response
        [$body, $flags] = $this->relay->receive();

        return $this->handleBody($body, $flags);
    }

    /**
     * @param string $method
     * @param mixed $payload
     * @param int $flags
     */
    private function send(string $method, $payload, int $flags = 0): void
    {
        $package = [$method . \pack('P', $this->seq) => Payload::TYPE_CONTROL | Payload::TYPE_RAW];

        if ($flags & Payload::TYPE_RAW && \is_scalar($payload)) {
            $package[(string)$payload] = $flags;
        } else {
            $package[$this->jsonEncode($payload)] = Payload::TYPE_JSON;
        }

        $this->relay->batch($package);
    }

    /**
     * @param mixed $payload
     * @return string
     * @throws ServiceException
     */
    private function jsonEncode($payload): string
    {
        try {
            $body = @\json_encode($payload, $this->getJsonOptions());

            if (\json_last_error() !== \JSON_ERROR_NONE) {
                throw new \JsonException(\json_last_error_msg());
            }
        } catch (\Throwable $e) {
            throw new ServiceException($e->getMessage(), 0x01, $e);
        }

        return $body;
    }

    /**
     * @param string $json
     * @return mixed
     * @throws ServiceException
     */
    private function jsonDecode(string $json)
    {
        try {
            $result = @\json_decode($json, true, 512, $this->getJsonOptions());

            if (\json_last_error() !== \JSON_ERROR_NONE) {
                throw new \JsonException(\json_last_error_msg());
            }
        } catch (\Throwable $e) {
            throw new ServiceException($e->getMessage(), 0x02, $e);
        }

        return $result;
    }

    /**
     * @return int
     */
    private function getJsonOptions(): int
    {
        if (\defined('JSON_THROW_ON_ERROR')) {
            return \JSON_THROW_ON_ERROR;
        }

        return 0;
    }

    /**
     * Handle response body.
     *
     * @param string $body
     * @param int $flags
     *
     * @return mixed
     * @throws ServiceException
     */
    protected function handleBody(string $body, int $flags)
    {
        if ($flags & Payload::TYPE_ERROR && $flags & Payload::TYPE_RAW) {
            throw new ServiceException(\sprintf(self::ERROR_SERVER_ERROR, $this->getRelayAsString(), $body));
        }

        if ($flags & Payload::TYPE_RAW) {
            return $body;
        }

        return $this->jsonDecode($body);
    }

    /**
     * @return string
     */
    private function getRelayAsString(): string
    {
        if ($this->relay instanceof \Stringable) {
            return (string)$this->relay;
        }

        return \get_class($this->relay);
    }
}
