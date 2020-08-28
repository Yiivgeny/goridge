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
use Spiral\Goridge\Sequence\IntSequence;
use Spiral\Goridge\Sequence\SequenceInterface;

abstract class RemoteProcedureCall implements RemoteProcedureCallInterface, \Stringable
{
    /**
     * @var RelayInterface
     */
    protected $relay;

    /**
     * @var SequenceInterface
     */
    private $sequence;

    /**
     * @param RelayInterface $relay
     * @param SequenceInterface|null $sequence
     */
    public function __construct(RelayInterface $relay, SequenceInterface $sequence = null)
    {
        $this->relay = $relay;
        $this->sequence = $sequence ?? new IntSequence();
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

    /**
     * @return int|string
     */
    protected function sequence()
    {
        return $this->sequence->current();
    }

    /**
     * @return void
     */
    protected function increment(): void
    {
        $this->sequence->next();
    }
}
