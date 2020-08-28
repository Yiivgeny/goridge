<?php

declare(strict_types=1);

namespace Spiral\Tests\Goridge\Legacy;

use Spiral\Goridge\RelayInterface;

class Relay implements RelayInterface
{
    /** @var RelayInterface */
    private $relay;

    public function __construct(RelayInterface $relay)
    {
        $this->relay = $relay;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->relay;
    }

    public function send($payload, int $flags = null): void
    {
        $this->relay->send($payload, $flags);
    }

    public function batch(iterable $payload): void
    {
        $this->relay->batch($payload);
    }

    public function receive(): array
    {
        return $this->relay->receive();
    }

    public function receiveSync(int &$flags = null)
    {
        return $this->relay->receiveSync($flags);
    }
}
