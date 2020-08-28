<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Goridge;

use Spiral\Goridge\Exception\InvalidArgumentException;
use Spiral\Goridge\Exception\PrefixException;
use Spiral\Goridge\Exception\RelayException;
use Spiral\Goridge\Exception\TransportException;
use Spiral\Goridge\Relay\SocketRelay\ConnectionInterface;
use Spiral\Goridge\Relay\SocketRelay\TcpConnection;
use Spiral\Goridge\Relay\SocketRelay\UnixConnection;

/**
 * Communicates with remote server/client over be-directional socket using byte payload:
 *
 * [ prefix       ][ payload                               ]
 * [ 1+8+8 bytes  ][ message length|LE ][message length|BE ]
 *
 * prefix:
 * [ flag       ][ message length, unsigned int 64bits, LittleEndian ]
 */
class SocketRelay extends Relay
{
    /**
     * @var string
     */
    private const ERROR_TCP_REQUIRED_PORT = 'No port given for TPC socket on "%s"';

    /**
     * @var string
     */
    private const ERROR_UNIX_INVALID_PORT = 'Unix socket cannot contain port, but "%d" given';

    /**
     * @var string
     */
    private const ERROR_INVALID_SOCKET = 'Unrecognized connection type %d on "%s"';

    /**
     * Provides default tcp access to a socket stream connection.
     *
     * @var int
     */
    public const SOCK_TCP = 0;

    /**
     * Provides access to a socket stream connection in the Unix domain.
     *
     * @var int
     */
    public const SOCK_UNIX = 1;

    /**
     * Typo in the name of the constant. An alias of {@see SocketRelay::SOCK_TCP} constant.
     *
     * @deprecated since 2.4 and will be removed in 3.0
     */
    public const SOCK_TPC = self::SOCK_TCP;

    /**
     * @var int
     */
    private $type;

    /**
     * @var ConnectionInterface|null
     */
    private $connection;

    /**
     * @var string
     */
    private $address;

    /**
     * @var int|null
     */
    private $port;

    /**
     * Example:
     * <code>
     *  $relay = new SocketRelay('localhost', 7000);
     *  $relay = new SocketRelay('/tmp/rpc.sock', null, Socket::UNIX_SOCKET);
     * </code>
     *
     * @param string $address Localhost, ip address or hostname.
     * @param int|null $port Ignored for UNIX sockets.
     * @param int $type Default: TCP_SOCKET
     *
     * @throws InvalidArgumentException
     */
    public function __construct(string $address, ?int $port = null, int $type = self::SOCK_TCP)
    {
        if (! \extension_loaded('sockets')) {
            throw new InvalidArgumentException("'sockets' extension not loaded");
        }

        $this->address = $address;
        $this->port = $port;
        $this->type = $type;
    }

    /**
     * @param int $type
     * @param string $addr
     * @param int|null $port
     * @return ConnectionInterface
     */
    private function createConnection(int $type, string $addr, int $port = null): ConnectionInterface
    {
        switch ($type) {
            case self::SOCK_TCP:
                if ($port === null) {
                    throw new InvalidArgumentException(\sprintf(self::ERROR_TCP_REQUIRED_PORT, $addr));
                }

                return new TcpConnection($addr, $port);

            case self::SOCK_UNIX:
                if ($port !== null) {
                    throw new InvalidArgumentException(\sprintf(self::ERROR_UNIX_INVALID_PORT, $port));
                }

                return new UnixConnection($addr);

            default:
                throw new InvalidArgumentException(\sprintf(self::ERROR_INVALID_SOCKET, $type, $addr));
        }
    }

    /**
     * Destruct connection and disconnect.
     */
    public function __destruct()
    {
        if ($this->isConnected()) {
            $this->close();
        }
    }

    /**
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->connection !== null;
    }

    /**
     * Close connection.
     *
     * @throws RelayException
     */
    public function close(): bool
    {
        if (! $this->isConnected()) {
            return false;
        }

        try {
            $this->connection = null;
            \gc_collect_cycles();
        } catch (\Throwable $e) {
            throw new RelayException($e->getMessage(), 0x01, $e);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        if ($this->port) {
            return $this->address . ':' . $this->port;
        }

        return $this->address;
    }

    /**
     * {@inheritDoc}
     * @return self
     */
    public function sendPackage(string $header, ?int $headerFlags, string $body, ?int $bodyFlags = null): Relay
    {
        $this->connect();

        return parent::sendPackage($header, $headerFlags, $body, $bodyFlags);
    }

    /**
     * Ensure socket connection. Returns true if socket successfully connected
     * or have already been connected.
     *
     * @return bool
     * @throws RelayException
     */
    public function connect(): bool
    {
        if ($this->isConnected()) {
            return false;
        }

        try {
            $this->connection = $this->createConnection($this->type, $this->address, $this->port);
        } catch (\Throwable $e) {
            throw new RelayException($e->getMessage(), 0x02, $e);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     * @return self
     */
    public function send(string $payload, ?int $flags = null): Relay
    {
        $this->connect();

        return parent::send($payload, $flags);
    }

    /**
     * {@inheritdoc}
     */
    public function receiveSync(?int &$flags = null): ?string
    {
        $this->connect();

        return parent::receiveSync($flags);
    }

    /**
     * {@inheritDoc}
     */
    protected function read(int $length): string
    {
        try {
            return $this->connection->read($length);
        } catch (\Throwable $e) {
            throw new RelayException($e->getMessage(), 0x03, $e);
        }
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @return int|null
     */
    public function getPort(): ?int
    {
        return $this->port ?: null;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * {@inheritDoc}
     */
    protected function write(string $body, int $length): void
    {
        try {
            \file_put_contents('out.txt', $body);
            $this->connection->write($body, $length);
        } catch (\Throwable $e) {
            throw new RelayException($e->getMessage(), 0x04, $e);
        }
    }
}
