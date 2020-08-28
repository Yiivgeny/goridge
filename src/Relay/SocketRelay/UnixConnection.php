<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Goridge\Relay\SocketRelay;

class UnixConnection extends Connection
{
    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        parent::__construct($path);
    }

    /**
     * {@inheritDoc}
     */
    protected function initSocket()
    {
        return $this->createSocket(\AF_UNIX, \SOCK_STREAM);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return 'unix://' . $this->addr;
    }
}
