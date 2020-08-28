<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Goridge\Relay\SocketRelay;

use Spiral\Goridge\Relay\Provider;
use Spiral\Goridge\RelayInterface;
use Spiral\Goridge\SocketRelay;

class SocketProvider extends Provider
{
    /**
     * @var string
     */
    public const PROTOCOL_TCP = 'tcp';

    /**
     * @var string
     */
    public const PROTOCOL_UNIX = 'unix';

    /**
     * @param string $protocol
     * @return bool
     */
    public function match(string $protocol): bool
    {
        return \in_array($protocol, [static::PROTOCOL_TCP, static::PROTOCOL_UNIX], true);
    }

    /**
     * @param string $protocol
     * @param string $signature
     * @return RelayInterface
     */
    public function create(string $protocol, string $signature): RelayInterface
    {
        if ($protocol === static::PROTOCOL_UNIX) {
            return new SocketRelay($signature, null, SocketRelay::SOCK_UNIX);
        }

        [$host, $port] = $this->parseSignature($protocol, $signature);

        return new SocketRelay($host, $port, SocketRelay::SOCK_TCP);
    }

    /**
     * @param string $protocol
     * @param string $signature
     * @return array
     */
    private function parseSignature(string $protocol, string $signature): array
    {
        $info = \parse_url($signature);

        if (! \is_array($info)) {
            throw $this->formatException($protocol, $signature);
        }

        $host = $info['host'] ?? $info['path'] ?? null;

        if ($host === null) {
            throw $this->formatException($protocol, $signature);
        }

        return [$host, $info['port'] ?? null];
    }
}
