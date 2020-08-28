<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Goridge\Protocol;

use Spiral\Goridge\Exception\TransportException;
use Spiral\Goridge\Relay\Payload;

/**
 * Communicates with remote server/client using byte payload:
 *
 * [ 17b PREFIX ][ PAYLOAD ]
 *      ↓
 * > 17b prefix is:
 * > [ 1 byte     ][ 8 bytes           ][ 8 bytes           ]
 * > [ flag       ][ message length|LE ][ message length|BE ]
 *
 * @psalm-import-type TEncodedMessage from EncoderInterface
 */
class GoridgeV2 extends Protocol
{
    /**
     * @var int
     */
    public const DEFAULT_CHUNK_SIZE = 65536;

    /**
     * @var string
     */
    private const ERROR_BAD_HEADER = 'Invalid package header';

    /**
     * @var string
     */
    private const ERROR_BAD_CHECKSUM = 'Invalid package checksum';

    /**
     * @var string
     */
    private const ERROR_DECODE_EMPTY_DATA = 'Unable to send non empty data with PAYLOAD_NONE flag';

    /**
     * @var string
     */
    private const ERROR_DECODE = 'Unable to pack message data';

    /**
     * @var int
     */
    private const PREFIX_SIZE = 1 + 8 + 8;

    /**
     * @var int
     */
    private $chunkSize;

    /**
     * @param int $chunkSize
     */
    public function __construct(int $chunkSize = self::DEFAULT_CHUNK_SIZE)
    {
        $this->chunkSize = $chunkSize;
    }

    /**
     * {@inheritDoc}
     */
    public function encode(string $message, int $flags = Payload::TYPE_JSON): array
    {
        return $this->packMessage($message, $flags);
    }

    /**
     * Packs the message in Goridge v2 format and returns an array (tuple)
     * of the content (string) and the length of this content.
     *
     * @param string $payload
     * @param int $flags
     * @return array
     *
     * @psalm-return TEncodedMessage
     */
    private function packMessage(string $payload, int $flags): array
    {
        $size = \strlen($payload);

        if ($flags & Payload::TYPE_EMPTY && $size !== 0) {
            throw new TransportException(self::ERROR_DECODE_EMPTY_DATA);
        }

        $body = \pack('CPJ', $flags, $size, $size);

        if (! \is_string($body)) {
            throw new TransportException(self::ERROR_DECODE);
        }

        if (! ($flags & Payload::TYPE_EMPTY)) {
            $body .= $payload;
        }

        return [
            static::ENCODED_MESSAGE_BODY => $body,
            static::ENCODED_MESSAGE_SIZE => $size + self::PREFIX_SIZE,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function decode(): \Generator
    {
        yield from $header = $this->readHeader();

        [$size, $flags] = $header->getReturn();

        yield from $body = $this->readBody($size);

        return [
            static::DECODED_MESSAGE_BODY  => $body->getReturn(),
            static::DECODED_MESSAGE_FLAGS => $flags,
        ];
    }

    /**
     * Reads the message header and returns the further message length and
     * flags in array format "[0 => $length, 1 => $flags]".
     *
     * @return \Generator
     * @psalm-return \Generator<int, int, string, array{0: int, 1: int}>
     */
    private function readHeader(): \Generator
    {
        /** @psalm-var array{flags: int, size: int, revs: int}|false $result */
        $result = \unpack('Cflags/Psize/Jrevs', yield self::PREFIX_SIZE);

        if (! \is_array($result)) {
            throw new TransportException(self::ERROR_BAD_HEADER, 0x01);
        }

        if ($result['size'] !== $result['revs']) {
            throw new TransportException(self::ERROR_BAD_CHECKSUM, 0x02);
        }

        return [$result['size'], $result['flags']];
    }

    /**
     * @param int $size
     * @return \Generator
     *
     * @psalm-return \Generator<array-key, int, string, string>
     */
    private function readBody(int $size): \Generator
    {
        if ($size === 0) {
            return '';
        }

        [$result, $bytesLeft] = ['', $size];

        while ($bytesLeft > 0) {
            $result .= yield $length = \min($this->chunkSize, $bytesLeft);

            $bytesLeft -= $length;
        }

        return $result;
    }
}
