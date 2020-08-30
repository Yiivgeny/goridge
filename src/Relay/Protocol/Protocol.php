<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Goridge\Relay\Protocol;

abstract class Protocol implements ProtocolInterface
{
    /**
     * @param DecoderInterface $decoder
     * @param \Closure $reader
     * @return DecodedMessageInterface
     */
    public static function decodeThrough(DecoderInterface $decoder, \Closure $reader): DecodedMessageInterface
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
     * @return EncodedMessageInterface
     *
     * @psalm-param iterable<string, Payload::TYPE_*|null> $payload
     * @psalm-return TEncodedMessage
     */
    public static function encodeBatch(EncoderInterface $encoder, iterable $payload): EncodedMessageInterface
    {
        [$buffer, $size] = ['', 0];

        foreach ($payload as $message => $flags) {
            $encoded = $encoder->encode((string)$message, (int)$flags);

            /** @psalm-suppress PossiblyInvalidOperand */
            $size += $encoded->size;
            $buffer .= $encoded->body;
        }

        return new EncodedMessage($buffer, $size);
    }
}
