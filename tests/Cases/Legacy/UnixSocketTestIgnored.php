<?php

/**
 * Dead simple, high performance, drop-in bridge to Golang RPC with zero dependencies
 *
 * @author Wolfy-J
 */

declare(strict_types=1);

namespace Spiral\Tests\Goridge\Legacy;

use Spiral\Goridge\RPC;
use Spiral\Goridge\SocketRelay;

class UnixSocketTestIgnored extends \Spiral\Tests\Goridge\UnixSocketTestIgnored
{
    protected function makeRPC(): RPC
    {
        return new RPC(new Relay(new SocketRelay(static::SOCK_ADDR, static::SOCK_PORT, static::SOCK_TYPE)));
    }
}
