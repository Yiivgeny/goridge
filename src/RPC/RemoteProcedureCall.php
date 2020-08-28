<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Goridge\RPC;

use Spiral\Goridge\RelayInterface;

abstract class RemoteProcedureCall implements RemoteProcedureCallInterface, \Stringable
{
    /**
     * @var RelayInterface
     */
    protected $relay;

    /**
     * @param RelayInterface $relay
     */
    public function __construct(RelayInterface $relay)
    {
        $this->relay = $relay;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        if ($this->relay instanceof \Stringable) {
            return (string)$this->relay;
        }

        return '<' . \get_class($this->relay) . '>';
    }
}
