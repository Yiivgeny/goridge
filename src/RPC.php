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
use Spiral\Goridge\RPC\InteractWithJsonTrait;
use Spiral\Goridge\RPC\RemoteProcedureCall;

/**
 * RPC bridge to Golang net/rpc package over Goridge protocol.
 */
class RPC extends RemoteProcedureCall
{
    use InteractWithJsonTrait;

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
    private const ERROR_BAD_HEADER = 'Invalid RPC server %s package header';

    /**
     * @var string
     */
    private const ERROR_MISMATCH_METHOD =
        'During a call method %s:%d on the RPC server %s, it ' .
        'returned an incorrect response indicating method %s:%d';

    /**
     * @var int
     */
    private const HEADER_PAYLOAD_TYPE = Payload::TYPE_CONTROL | Payload::TYPE_RAW;

    /**
     * @param string $method
     * @param mixed $payload An binary data or array of arguments for complex types.
     * @param int $flags Payload control flags.
     * @return mixed
     * @throws RelayException
     */
    public function call(string $method, $payload, int $flags = Payload::TYPE_JSON)
    {
        $this->send($method, $payload, $flags);

        [$body, $flags] = $this->relay->receive();

        if (! ($flags & Payload::TYPE_CONTROL)) {
            throw new TransportException(\sprintf(self::ERROR_HEADER_MISSING, $this));
        }

        $result = \unpack('Ps', \substr($body, -8));

        if (! \is_array($result)) {
            throw new TransportException(\sprintf(self::ERROR_BAD_HEADER, $this), 0x01);
        }

        $result['m'] = \substr($body, 0, -8);

        if ($result['m'] !== $method || $result['s'] !== $this->sequence()) {
            $message = \vsprintf(self::ERROR_MISMATCH_METHOD, [
                $method,
                $this->sequence(),
                $this,
                $result['m'],
                $result['s'],
            ]);

            throw new TransportException($message);
        }

        // Request id++
        $this->increment();

        // Wait for the response
        [$body, $flags] = $this->relay->receive();

        return $this->handleBody($body, $flags);
    }

    /**
     * @param string $method
     * @param mixed $payload
     * @param int $flags
     */
    private function send(string $method, $payload, int $flags): void
    {
        // To pass to the batching send method, any iterator is required
        // that can store the same keys.
        //
        // This feature can be implemented with a generator.
        $package = function () use ($method, $payload, $flags) {
            yield from $this->packHeader($method);
            yield from $this->packPayload($payload, $flags);
        };

        $this->relay->batch($package());
    }

    /**
     * @param string $method
     * @return iterable
     */
    private function packHeader(string $method): iterable
    {
        yield $method . \pack('P', $this->sequence()) => self::HEADER_PAYLOAD_TYPE;
    }

    /**
     * @param mixed $payload
     * @param int $flags
     * @return iterable
     */
    private function packPayload($payload, int $flags): iterable
    {
        if ($flags & Payload::TYPE_RAW && \is_scalar($payload)) {
            yield (string)$payload => $flags;

            return;
        }

        yield $this->jsonEncode($payload) => Payload::TYPE_JSON;
    }

    /**
     * Handle response body.
     *
     * @param string $body
     * @param int $flags
     * @return mixed
     * @throws ServiceException
     */
    protected function handleBody(string $body, int $flags)
    {
        if ($flags & Payload::TYPE_ERROR && $flags & Payload::TYPE_RAW) {
            throw new ServiceException(\sprintf(self::ERROR_SERVER_ERROR, $this, $body));
        }

        if ($flags & Payload::TYPE_RAW) {
            return $body;
        }

        return $this->jsonDecode($body);
    }
}
