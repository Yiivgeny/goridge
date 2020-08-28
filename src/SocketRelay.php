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

class SocketRelay extends Relay
{
    /**
     * @var string
     */
    private const ERROR_TCP_REQUIRED_PORT = 'No port given for TPC socket on "%s"';

    /**
     * @var string
     */
    private const ERROR_TCP_INVALID_PORT = 'Invalid port given for TPC socket on "%s"';

    /**
     * @var string
     */
    private const ERROR_SOCKET_INIT = 'Unable to create socket %s';

    /**
     * @var string
     */
    private const ERROR_SOCKET_CONNECT = 'Unable to establish connection %s';

    /**
     * @var string
     */
    private const ERROR_SOCKET_INVALID_TYPE = 'Unrecognized connection type %s on "%s"';

    /**
     * @var string
     */
    private const ERROR_SOCKET_CLOSED = 'Unable to close socket %s, socket already closed';

    /**
     * @var string
     */
    private const ERROR_SOCKET_READ = 'Unable to read data from socket %s';

    /**
     * @var string
     */
    private const ERROR_SOCKET_WRITE = 'Unable to write data into the socket %s';

    /**
     * @var string
     */
    private const ERROR_SOCKET_DISCONNECT = 'Unable to close connection %s';

    /**
     * @var string
     */
    private const ERROR_SOCKET_UNKNOWN = 'Unknown socket error';

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
    private const SOCK_TCP_PORT_MIN = 1;

    /**
     * @var int
     */
    private const SOCK_TCP_PORT_MAX = 65535;

    /**
     * @var string
     */
    private $address;

    /**
     * @var int|null
     */
    private $port;

    /**
     * @var int
     */
    private $type;

    /**
     * @var resource|null
     */
    private $socket;

    /**
     * @var bool
     */
    private $connected = false;

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

        switch ($type) {
            case self::SOCK_TCP:
                $this->assertTcpSocket($address, $port);
                break;

            case self::SOCK_UNIX:
                $port = null;
                break;

            default:
                $message = \sprintf(self::ERROR_SOCKET_INVALID_TYPE, $type, $address);

                throw new InvalidArgumentException($message);
        }

        $this->address = $address;
        $this->port = $port;
        $this->type = $type;

        parent::__construct();
    }


    /**
     * Checks that the passed arguments are valid for TCP socket,
     * otherwise throws an error.
     *
     * @param string $address
     * @param int|null $port
     */
    private function assertTcpSocket(string $address, ?int $port): void
    {
        if ($port === null) {
            throw new InvalidArgumentException(\sprintf(self::ERROR_TCP_REQUIRED_PORT, $address));
        }

        if ($port < self::SOCK_TCP_PORT_MIN || $port > self::SOCK_TCP_PORT_MAX) {
            throw new InvalidArgumentException(\sprintf(self::ERROR_TCP_INVALID_PORT, $address));
        }
    }

    /**
     * Destruct connection and disconnect.
     */
    public function __destruct()
    {
        if ($this->isConnected()) {
            try {
                $this->close();
            } catch (\Throwable $e) {
                //
                // Skip any errors of closing a socket in the destructor,
                // since these exception are extremely difficult to catch.
                //
                // Which, in turn, can lead to unstable behavior, depending
                // on the PHP version and the characteristics of its GC.
                //
                // However, custom errors can be used to catch such exception.
                // In this case, they can be intercepted and processed
                // correctly in the future.
                //
                @\trigger_error($e->getMessage(), \E_USER_NOTICE);
            }
        }
    }

    /**
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }

    /**
     * Close connection.
     *
     * @throws RelayException
     */
    public function close(): void
    {
        if (! $this->isConnected()) {
            throw new RelayException(\sprintf(self::ERROR_SOCKET_CLOSED, $this));
        }

        \error_clear_last();

        try {
            @\socket_close($this->socket);

            //
            // Note that the socket_close() function throws a warning,
            // not a socket error.
            //
            // It can occur if a socket is closed unexpectedly.
            //
            if ($error = \error_get_last()) {
                $suffix = $error['message'] ?? self::ERROR_SOCKET_UNKNOWN;

                throw new RelayException(\sprintf(self::ERROR_SOCKET_DISCONNECT, $this) . ', ' . $suffix);
            }
        } finally {
            $this->connected = false;
            $this->socket = null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        if ($this->type === self::SOCK_TCP) {
            return "tcp://{$this->address}:{$this->port}";
        }

        return "unix://{$this->address}";
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
            return true;
        }

        $socket = $this->createSocketOrFail();

        $status = @\socket_connect($socket, $this->address, $this->port ?? 0);

        if ($status === false) {
            $suffix = \socket_strerror(\socket_last_error());

            throw new RelayException(\sprintf(self::ERROR_SOCKET_CONNECT, $this) . ', ' . $suffix);
        }

        $this->socket = $socket;
        $this->connected = true;

        return true;
    }

    /**
     * @return resource
     * @throws RelayException
     */
    private function createSocketOrFail()
    {
        $socket = $this->createSocket();

        if ($socket === false) {
            throw new RelayException(\sprintf(self::ERROR_SOCKET_INIT, $this));
        }

        return $socket;
    }

    /**
     * @return resource|false
     */
    private function createSocket()
    {
        if ($this->type === self::SOCK_UNIX) {
            return @\socket_create(\AF_UNIX, \SOCK_STREAM, 0);
        }

        return @\socket_create(\AF_INET, \SOCK_STREAM, \SOL_TCP);
    }

    /**
     * {@inheritDoc}
     */
    protected function read(int $length): string
    {
        $this->connect();

        $bytes = @\socket_recv($this->socket, $body, $length, \MSG_WAITALL);

        if ($body === null || $bytes !== $length) {
            $message = \sprintf(self::ERROR_SOCKET_READ, \socket_strerror(\socket_last_error($this->socket)));

            throw new PrefixException($message);
        }

        return $body;
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
        return $this->port;
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
        $this->connect();

        $status = @\socket_send($this->socket, $body, $length, 0);

        if ($status === false) {
            throw new TransportException(\sprintf(self::ERROR_SOCKET_WRITE, $this));
        }
    }
}
