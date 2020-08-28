<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Goridge\Relay\SocketRelay;

use Spiral\Goridge\Exception\TransportException;

abstract class Connection implements ConnectionInterface
{
    /**
     * @var string
     */
    private const ERROR_INIT = 'Unable to create socket %s';

    /**
     * @var string
     */
    private const ERROR_CONNECT = 'Unable to establish connection %s';

    /**
     * @var string
     */
    private const ERROR_DISCONNECT = 'Unable to close connection %s';

    /**
     * @var string
     */
    private const ERROR_READ = 'Unable to read data from socket %s';

    /**
     * @var string
     */
    private const ERROR_WRITE = 'Unable to read data from socket %s';

    /**
     * @var resource|null
     */
    protected $socket;

    /**
     * @var string
     */
    protected $addr;

    /**
     * @var int
     */
    protected $port;

    /**
     * @param string $addr
     * @param int $port
     */
    public function __construct(string $addr, int $port = 0)
    {
        $this->addr = $addr;
        $this->port = $port;

        $this->socket = $this->initSocket();

        $status = @\socket_connect($this->socket, $this->addr, $this->port);

        if ($status === false) {
            throw $this->transportException(self::ERROR_CONNECT, 0x03, $this);
        }
    }

    /**
     * @param string $message
     * @param int $code
     * @param mixed ...$args
     * @return TransportException
     */
    protected function transportException(string $message, int $code, ...$args): TransportException
    {
        $message = \vsprintf($message, $args);

        if ($suffix = \error_get_last()) {
            $message .= ', ' . ($suffix['message'] ?? 'Unknown error');
        }

        if ($suffix = $this->getLastErrorMessage()) {
            $message .= ', ' . $suffix;
        }

        return new TransportException($message, $code);
    }

    /**
     * @return resource
     */
    abstract protected function initSocket();

    /**
     * @param int $length
     * @return string
     */
    public function read(int $length): string
    {
        $bytes = \socket_recv($this->socket, $body, $length, \MSG_WAITALL);

        if ($body === null || $bytes !== $length) {
            throw $this->transportException(self::ERROR_READ, 0x01, $this);
        }

        return $body;
    }

    /**
     * @return string|null
     */
    protected function getLastErrorMessage(): ?string
    {
        $error = @\socket_last_error($this->socket);

        if (\is_int($error) && $error > 0) {
            return \socket_strerror($error);
        }

        return null;
    }

    /**
     * @param string $body
     * @param int|null $length
     */
    public function write(string $body, int $length = null): void
    {
        $status = @\socket_send($this->socket, $body, $length ?? \strlen($body), 0);

        if ($status === false) {
            throw $this->transportException(self::ERROR_WRITE, 0x02, $this);
        }
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        \error_clear_last();

        @\socket_close($this->socket);

        if ($error = \error_get_last()) {
            throw $this->transportException(self::ERROR_DISCONNECT, 0x04, $this);
        }
    }

    /**
     * @param int $domain
     * @param int $type
     * @param int $protocol
     * @return resource
     */
    protected function createSocket(int $domain, int $type, int $protocol = 0)
    {
        $socket = @\socket_create($domain, $type, $protocol);

        if ($socket === false) {
            throw $this->transportException(self::ERROR_INIT, 0x05, $this);
        }

        return $socket;
    }
}
