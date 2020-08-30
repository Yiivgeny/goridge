<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Goridge\Relay\Protocol;

use Spiral\Goridge\Exception\TransportException;
use Spiral\Goridge\Relay\Payload;

interface EncoderInterface
{
    /**
     * Packs the message to protocol format and returns {@see DecodedMessageInterface} data
     * transfer object instance.
     *
     * <code>
     *  [$body, $length] = $encoder->encode('Example');
     * </code>
     *
     * @param string $body The protocol's message body.
     * @param int $flags The message's flags.
     * @return EncodedMessageInterface
     * @throws TransportException in case of decoding error.
     *
     * @psalm-param Payload::TYPE_* $flags
     */
    public function encode(string $body, int $flags = Payload::TYPE_JSON): EncodedMessageInterface;
}
