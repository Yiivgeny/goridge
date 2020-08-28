<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Goridge\Protocol;

use Spiral\Goridge\Relay\Payload;

/**
 * @psalm-import-type TDecodedMessage from DecoderInterface
 * @psalm-import-type TEncodedMessage from EncoderInterface
 */
abstract class Protocol implements ProtocolInterface
{
    /**
     * @param DecoderInterface $decoder
     * @param \Closure $reader
     * @return array
     *
     * @psalm-return TDecodedMessage
     */
    public static function decodeThrough(DecoderInterface $decoder, \Closure $reader): array
    {
        $stream = $decoder->decode();

        while ($stream->valid()) {
            $chunk = $reader($stream->current());

            $stream->send($chunk);
        }

        return $stream->getReturn();
    }

    /**
     * @param EncoderInterface $encoder
     * @param iterable $payload
     * @return array
     *
     * @psalm-param iterable<string, Payload::TYPE_*> $payload
     * @psalm-return TEncodedMessage
     */
    public static function encodeBatch(EncoderInterface $encoder, iterable $payload): array
    {
        [$buffer, $length] = ['', 0];

        foreach ($payload as $message => $flags) {
            $encoded = $encoder->encode((string)$message, (int)$flags);

            /** @psalm-suppress PossiblyInvalidOperand */
            $length += $encoded[static::ENCODED_MESSAGE_SIZE];
            $buffer .= $encoded[static::ENCODED_MESSAGE_BODY];
        }

        return [
            static::ENCODED_MESSAGE_BODY => $buffer,
            static::ENCODED_MESSAGE_SIZE => $length,
        ];
    }
}
