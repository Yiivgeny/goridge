<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Goridge\Relay\SocketRelay;

interface ConnectionInterface extends \Stringable
{
    /**
     * @param int $length
     * @return string
     */
    public function read(int $length): string;

    /**
     * @param string $body
     * @param int|null $length
     */
    public function write(string $body, int $length = null): void;
}
