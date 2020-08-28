<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Goridge;

use Spiral\Goridge\Protocol\GoridgeV2;
use Spiral\Goridge\Protocol\Protocol;
use Spiral\Goridge\Protocol\ProtocolInterface;
use Spiral\Goridge\Relay\Factory;
use Spiral\Goridge\Relay\Payload;
use Spiral\Goridge\Relay\SocketProvider;
use Spiral\Goridge\Relay\StreamProvider;

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
     * @var ProtocolInterface
     */
    private $protocol;

    /**
     * @param ProtocolInterface|null $protocol
     */
    public function __construct(ProtocolInterface $protocol = null)
    {
        $this->protocol = $protocol ?? new GoridgeV2();
    }

    /**
     * @param ProtocolInterface $protocol
     * @return $this
     */
    public function over(ProtocolInterface $protocol): self
    {
        $this->protocol = $protocol;

        return $this;
    }

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
     */
    public function send(string $payload, ?int $flags = null): void
    {
        [$message, $size] = $this->protocol->encode($payload, $flags ?? Payload::TYPE_JSON);

        $this->write($message, $size);
    }

    /**
     * {@inheritDoc}
     */
    public function batch(iterable $payload): void
    {
        [$message, $size] = Protocol::encodeBatch($this->protocol, $payload);

        $this->write($message, $size);
    }

    /**
     * {@inheritDoc}
     */
    public function receive(): array
    {
        $stream = $this->protocol->decode();

        while ($stream->valid()) {
            $chunk = $this->read($stream->current());

            $stream->send($chunk);
        }

        return $stream->getReturn();
    }

    /**
     * {@inheritdoc}
     */
    public function receiveSync(?int &$flags = null): ?string
    {
        [$message, $flags] = $this->receive();

        return $message === '' ? null : $message;
    }

    /**
     * {@inheritDoc}
     */
    public function sendPackage(string $header, ?int $headerFlags, string $body, ?int $bodyFlags = null): void
    {
        $this->batch([$header => $headerFlags, $body => $bodyFlags]);
    }

    /**
     * @param string $body
     * @param int $length
     */
    abstract protected function write(string $body, int $length): void;

    /**
     * @param int $length
     * @return string
     */
    abstract protected function read(int $length): string;
}
