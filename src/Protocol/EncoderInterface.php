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
use Spiral\Goridge\Exception\TransportException;

/**
 * @psalm-type TEncodedMessage = array { 0: string, 1: int }
 */
interface EncoderInterface
{
    /**
     * The key of the array returned from the {@see EncoderInterface::encode}
     * method containing the encoded message body.
     *
     * @var int
     */
    public const ENCODED_MESSAGE_BODY = 0x00;

    /**
     * The key of the array returned from the {@see EncoderInterface::encode}
     * method containing the encoded message size (in bytes).
     *
     * @var int
     */
    public const ENCODED_MESSAGE_SIZE = 0x01;

    /**
     * Packs the message to protocol format and returns an array (tuple)
     * of the content (string) and the size in bytes (int) of this content.
     *
     * <code>
     *  [$body, $length] = $encoder->encode('Example');
     * </code>
     *
     * @param string $message The protocol's message body.
     * @param int $flags The message's flags.
     * @return array The encoded message and its size in bytes.
     * @throws TransportException in case of decoding error.
     *
     * @psalm-param Payload::TYPE_* $flags
     * @psalm-return TEncodedMessage
     */
    public function encode(string $message, int $flags = Payload::TYPE_JSON): array;
}
