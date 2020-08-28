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
    private $seq = 0;

    /**
     * @param string $method
     * @param mixed  $payload An binary data or array of arguments for complex types.
     * @param int    $flags   Payload control flags.
     * @return mixed
     * @throws RelayException
     */
    public function call(string $method, $payload, int $flags = Payload::TYPE_JSON)
    {
        $this->send($method, $payload, $flags);

        [$body, $flags] = $this->relay->receive();

        if (!($flags & Payload::TYPE_CONTROL)) {
            throw new TransportException(\sprintf(self::ERROR_HEADER_MISSING, $this));
        }

        $result = \unpack('Ps', \substr($body, -8));

        if (! \is_array($result)) {
            throw new TransportException(\sprintf(self::ERROR_BAD_HEADER, $this), 0x01);
        }

        $result['m'] = \substr($body, 0, -8);

        if ($result['m'] !== $method || $result['s'] !== $this->seq) {
            $message = \vsprintf(self::ERROR_MISMATCH_METHOD, [
                $method, $this->seq,
                $this,
                $result['m'], $result['s']
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
    private function send(string $method, $payload, int $flags): void
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
            throw new ServiceException(\sprintf(self::ERROR_SERVER_ERROR, $this, $body));
        }

        if ($flags & Payload::TYPE_RAW) {
            return $body;
        }

        return $this->jsonDecode($body);
    }
}
