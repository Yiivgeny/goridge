<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Goridge;

use Spiral\Goridge\Exception\PrefixException;
use Spiral\Goridge\Exception\TransportException;
use Spiral\Goridge\Relay\Factory;
use Spiral\Goridge\Relay\PayloadInterface;
use Spiral\Goridge\Relay\SocketRelay\SocketProvider;
use Spiral\Goridge\Relay\StreamRelay\StreamProvider;

abstract class Relay implements RelayInterface, SendPackageRelayInterface, StringableRelayInterface
{
    /**
     * @deprecated since 2.5 and will be removed in 3.0. Please use {@see SocketProvider::PROTOCOL_TCP} const instead.
     * @var string
     */
    public const TCP_SOCKET = SocketProvider::PROTOCOL_TCP;

    /**
     * @deprecated since 2.5 and will be removed in 3.0. Please use {@see SocketProvider::PROTOCOL_UNIX} const instead.
     * @var string
     */
    public const UNIX_SOCKET = SocketProvider::PROTOCOL_UNIX;

    /**
     * @deprecated since 2.5 and will be removed in 3.0. Please use {@see StreamProvider::PROTOCOL_PHP} const instead.
     * @var string
     */
    public const STREAM = StreamProvider::PROTOCOL_PIPES;

    /**
     * @var int
     */
    protected const PREFIX_SIZE = 17;

    /**
     * @var string
     */
    private const ERROR_WRITE_PACKING_ERROR = 'Unable to send payload with PAYLOAD_NONE flag';

    /**
     * @param string $connection
     * @return RelayInterface
     */
    public static function create(string $connection): RelayInterface
    {
        return (new Factory())->create($connection);
    }

    /**
     * {@inheritDoc}
     * @return self
     */
    public function send(string $payload, ?int $flags = null): self
    {
        $package = $this->packMessage($payload, $flags);

        $this->write($package['body'], self::PREFIX_SIZE + $package['size']);

        return $this;
    }

    /**
     * @param string $body
     * @param int $length
     */
    abstract protected function write(string $body, int $length): void;

    /**
     * {@inheritdoc}
     */
    public function receiveSync(?int &$flags = null): ?string
    {
        ['flags' => $flags, 'size' => $size] = $this->fetchPrefix();

        $result = '';

        if ($size !== 0) {
            $readBytes = $size;

            // Add ability to write to stream in a future
            while ($readBytes > 0) {
                $bufferLength = \min(self::BUFFER_SIZE, $readBytes);

                $result .= $this->read($bufferLength);
                $readBytes -= $bufferLength;
            }
        }

        \file_put_contents('in.txt', $result);

        return $result ?: null;
    }

    /**
     * @return array Prefix [flag, length]
     *
     * @throws PrefixException
     */
    protected function fetchPrefix(): array
    {
        $prefixBody = $this->read(self::PREFIX_SIZE);

        $result = \unpack('Cflags/Psize/Jrevs', $prefixBody);

        if (! \is_array($result)) {
            throw new PrefixException('Invalid prefix');
        }

        if ($result['size'] !== $result['revs']) {
            throw new PrefixException('Invalid prefix (checksum)');
        }

        return $result;
    }

    /**
     * @param int $length
     * @return string
     */
    abstract protected function read(int $length): string;

    /**
     * @param string $header
     * @param int|null $headerFlags
     * @param string $body
     * @param int|null $bodyFlags
     * @return $this
     */
    public function sendPackage(string $header, ?int $headerFlags, string $body, ?int $bodyFlags = null): self
    {
        $headerPackage = $this->packMessage($header, $headerFlags);
        $bodyPackage = $this->packMessage($body, $bodyFlags);

        $this->write(
            $headerPackage['body'] . $bodyPackage['body'],
            self::PREFIX_SIZE * 2 + $headerPackage['size'] + $bodyPackage['size']
        );

        return $this;
    }

    /**
     * @param string $payload
     * @param int|null $flags
     * @return array
     */
    private function packMessage(string $payload, ?int $flags = null): array
    {
        $size = \strlen($payload);

        if ($flags & PayloadInterface::PAYLOAD_NONE && $size !== 0) {
            throw new TransportException(self::ERROR_WRITE_PACKING_ERROR);
        }

        $body = \pack('CPJ', $flags, $size, $size);

        if (! ($flags & PayloadInterface::PAYLOAD_NONE)) {
            $body .= $payload;
        }

        return \compact('body', 'size');
    }
}
