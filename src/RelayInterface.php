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
use Spiral\Goridge\Relay\Protocol\GoridgeV2;
use Spiral\Goridge\Relay\Payload;

/**
 * @psalm-type TReceivedMessage = array {
 *  0: string,
 *  1: Payload::TYPE_*
 * }
 */
interface RelayInterface
{
    /**
     * Maximum payload size to read at once.
     *
     * @deprecated since 2.5 and will be removed in 3.0. Please use {@see GoridgeV2::DEFAULT_CHUNK_SIZE} const instead.
     */
    public const BUFFER_SIZE = 65536;

    /**
     * Must be set when no data to be sent.
     *
     * @deprecated since 2.5 and will be removed in 3.0. Please use {@see Payload::TYPE_EMPTY} const instead.
     */
    public const PAYLOAD_NONE = Payload::TYPE_EMPTY;

    /**
     * Must be set when data binary data.
     *
     * @deprecated since 2.5 and will be removed in 3.0. Please use {@see Payload::TYPE_RAW} const instead.
     */
    public const PAYLOAD_RAW = Payload::TYPE_RAW;

    /**
     * Must be set when data is error string or structure.
     *
     * @deprecated since 2.5 and will be removed in 3.0. Please use {@see Payload::TYPE_ERROR} const instead.
     */
    public const PAYLOAD_ERROR = Payload::TYPE_ERROR;

    /**
     * Defines that associated data must be treated as control data.
     *
     * @deprecated since 2.5 and will be removed in 3.0. Please use {@see Payload::TYPE_CONTROL} const instead.
     */
    public const PAYLOAD_CONTROL = Payload::TYPE_CONTROL;

    /**
     * @var int
     */
    public const RECEIVED_MESSAGE_BODY = 0x00;

    /**
     * @var int
     */
    public const RECEIVED_MESSAGE_FLAGS = 0x01;

    /**
     * Send payload message to another party.
     *
     * @param string $payload
     * @param int|null $flags Protocol control flags.
     * @return void
     * @throws RelayException
     *
     * @psalm-param Payload::TYPE_*|null $flags
     */
    public function send(string $payload, ?int $flags = null): void;

    /**
     * @param iterable $payload
     * @return void
     * @throws RelayException
     *
     * @psalm-param iterable<string, Payload::TYPE_*>|iterable<array-key, string> $payload
     */
    public function batch(iterable $payload): void;

    /**
     * Receive message from another party in sync/blocked mode. Message can be null.
     *
     * @param int|null $flags Response flags.
     * @return null|string
     * @throws RelayException
     *
     * @deprecated since 2.5 and will be removed in 3.0. Please use {@see RelayInterface::receive()} method instead.
     */
    public function receiveSync(?int &$flags = null);

    /**
     * Receive message from another party in sync/blocked mode.
     *
     * <code>
     *  [$message, $flags] = $relay->receive();
     * </code>
     *
     * @return array
     *
     * @psalm-return TReceivedMessage
     */
    public function receive(): array;
}
