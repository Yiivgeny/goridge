<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Goridge\Sequence;

class IntSequence extends Sequence
{
    /**
     * @var int
     */
    private const NOTICE_OVERFLOW =
        'Sequence identifier overflow (sequence + 1 > PHP_INT_MAX). ' .
        'The value will be reset to zero.';

    /**
     * @var int
     */
    public const INITIAL_SEQUENCE_NUMBER = 0;

    /**
     * @var int
     */
    private $sequence;

    /**
     * @param int $initial
     */
    public function __construct(int $initial = self::INITIAL_SEQUENCE_NUMBER)
    {
        $this->sequence = $initial;
    }

    /**
     * {@inheritDoc}
     */
    public function next(): void
    {
        if ($this->sequence === \PHP_INT_MAX) {
            $this->sequence = self::INITIAL_SEQUENCE_NUMBER;

            @\trigger_error(self::NOTICE_OVERFLOW, \E_USER_NOTICE);
        }

        ++$this->sequence;
    }

    /**
     * {@inheritDoc}
     *
     * @return int
     */
    public function current(): int
    {
        return $this->sequence;
    }

    /**
     * @return int[]
     * @psalm-return {value: int}
     */
    public function __debugInfo(): array
    {
        return [
            'value' => $this->sequence,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return (string)$this->sequence;
    }
}
