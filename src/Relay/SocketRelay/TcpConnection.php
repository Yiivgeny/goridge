<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Goridge\Relay\SocketRelay;

use Spiral\Goridge\Exception\InvalidArgumentException;

class TcpConnection extends Connection
{
    /**
     * @var string
     */
    private const ERROR_TCP_INVALID_PORT = 'Invalid port given for TPC socket on "%s"';

    /**
     * @var int
     */
    public const PORT_MIN = 1;

    /**
     * @var int
     */
    public const PORT_MAX = 65535;

    /**
     * @param string $addr
     * @param int $port
     */
    public function __construct(string $addr, int $port)
    {
        if ($port < static::PORT_MIN || $port > static::PORT_MAX) {
            throw new InvalidArgumentException(\sprintf(self::ERROR_TCP_INVALID_PORT, $addr));
        }

        parent::__construct($addr, $port);
    }

    /**
     * @return resource
     */
    protected function initSocket()
    {
        return $this->createSocket(\AF_INET, \SOCK_STREAM, \SOL_TCP);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return 'tcp://' . $this->addr . ':' . $this->port;
    }
}
