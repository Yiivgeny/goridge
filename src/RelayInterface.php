<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Goridge;

use Spiral\Goridge\Exception\TransportException;
use Spiral\Goridge\Relay\PayloadInterface;

interface RelayInterface extends PayloadInterface
{
    /**
     * Maximum payload size to read at once.
     */
    public const BUFFER_SIZE = 65536;

    /**
     * Send payload message to another party.
     *
     * @param string   $payload
     * @param int|null $flags Protocol control flags.
     * @return mixed|void
     *
     * @throws TransportException
     */
    public function send(string $payload, ?int $flags = null);

    /**
     * Receive message from another party in sync/blocked mode. Message can be null.
     *
     * @param int|null $flags Response flags.
     *
     * @return null|string
     *
     * @throws TransportException
     */
    public function receiveSync(?int &$flags = null);
}
