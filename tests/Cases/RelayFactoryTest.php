<?php

/**
 * Dead simple, high performance, drop-in bridge to Golang RPC with zero dependencies
 *
 * @author Valentin V
 */

declare(strict_types=1);

namespace Spiral\Tests\Goridge;

use PHPUnit\Framework\TestCase;
use Spiral\Goridge\Exception;
use Spiral\Goridge\Relay;
use Spiral\Goridge\SocketRelay;
use Spiral\Goridge\StreamRelay;
use Throwable;

class RelayFactoryTest extends TestCase
{
    /**
     * @dataProvider formatProvider
     * @param string $connection
     * @param bool   $expectedException
     */
    public function testFormat(string $connection, bool $expectedException = false): void
    {
        $this->assertTrue(true);
        if ($expectedException) {
            $this->expectException(Exception\RelayFactoryException::class);
        }

        try {
            Relay::create($connection);
        } catch (Exception\RelayFactoryException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            //do nothing, that's not a factory issue
        }
    }

    /**
     * @return iterable
     */
    public function formatProvider(): iterable
    {
        return [
            //format invalid
            'tcp:localhost:' => ['tcp:localhost:', true],
            'tcp:/localhost:' => ['tcp:/localhost:', true],
            'tcp//localhost:' => ['tcp//localhost:', true],
            'tcp//localhost' => ['tcp//localhost', true],
            //unknown provider
            'test://localhost' => ['test://localhost', true],
            //pipes require 2 args
            'pipes://localhost:' => ['pipes://localhost:', true],
            'pipes://localhost' => ['pipes://localhost', true],
            //invalid resources
            'pipes://stdin:test' => ['pipes://stdin:test', true],
            'pipes://test:stdout' => ['pipes://test:stdout', true],
            'pipes://test:test' => ['pipes://test:test', true],
            //valid format
            'tcp://localhost' => ['tcp://localhost'],
            'tcp://localhost:123' => ['tcp://localhost:123'],
            'unix://localhost:123' => ['unix://localhost:123'],
            'unix://rpc.sock' => ['unix://rpc.sock'],
            'unix:///tmp/rpc.sock' => ['unix:///tmp/rpc.sock'],
            'tcp://localhost:abc' => ['tcp://localhost:abc'],
            'pipes://stdin:stdout' => ['pipes://stdin:stdout'],
        ];
    }

    public function testTCP(): void
    {
        /** @var SocketRelay $relay */
        $relay = Relay::create('tcp://localhost:80');
        $this->assertInstanceOf(SocketRelay::class, $relay);
        $this->assertSame('localhost', $relay->getAddress());
        $this->assertSame(80, $relay->getPort());
        $this->assertSame(SocketRelay::SOCK_TCP, $relay->getType());
    }

    public function testUnix(): void
    {
        /** @var SocketRelay $relay */
        $relay = Relay::create('unix:///tmp/rpc.sock');
        $this->assertInstanceOf(SocketRelay::class, $relay);
        $this->assertSame('/tmp/rpc.sock', $relay->getAddress());
        $this->assertSame(SocketRelay::SOCK_UNIX, $relay->getType());
    }

    public function testPipes(): void
    {
        /** @var StreamRelay $relay */
        $relay = Relay::create('pipes://stdin:stdout');
        $this->assertInstanceOf(StreamRelay::class, $relay);
    }
}
