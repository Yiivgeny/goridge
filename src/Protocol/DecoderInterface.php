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
 * @psalm-type TDecodedMessage = array {
 *  0: string,
 *  1: Payload::TYPE_*
 * }
 */
interface DecoderInterface
{
    /**
     * The key of the array returned from the {@see \Generator::getReturn()}
     * which is returned from the {@see DecoderInterface::decode()} method
     * containing the decoded message body.
     *
     * @var int
     */
    public const DECODED_MESSAGE_BODY = 0x00;

    /**
     * The key of the array returned from the {@see \Generator::getReturn()}
     * which is returned from the {@see DecoderInterface::decode()} method
     * containing the decoded message flags.
     *
     * @var int
     */
    public const DECODED_MESSAGE_FLAGS = 0x01;

    /**
     * Returns the coroutine for reading from the source. Each "tick" of the
     * iterator passes the length to read from the source. The read value of
     * the chunk should be sent back.
     *
     * The result ({@see \Generator::getReturn()}) of the coroutine contains
     * the read and decoded message body and its flags ({@see Payload}) as a result of the
     * decoding.
     *
     * For example, if we work with resource streams, then the reading
     * will look like this:
     *
     * <code>
     *  $stream = $decoder->decode();
     *
     *  while ($stream->valid()) {
     *      $stream->send(
     *          fread($resource, $stream->current())
     *      );
     *  }
     *
     *  return $stream->getReturn();
     * </code>
     *
     * @return \Generator
     * @throws TransportException in case of decoding error.
     *
     * @psalm-return \Generator<array-key, int, string, TDecodedMessage>
     */
    public function decode(): \Generator;
}
