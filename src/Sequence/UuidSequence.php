<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Goridge\Sequence;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class UuidSequence extends Sequence
{
    /**
     * @var UuidInterface
     */
    private $value;

    /**
     * @var IntSequence
     */
    private $sequence;

    /**
     * @param string|null $value
     */
    public function __construct(string $value = null)
    {
        \assert(\class_exists(Uuid::class), 'ramsey/uuid >= 4.0 package required');

        $this->updateInternalSequence(new IntSequence());
        $this->bootUuid($value);
    }

    /**
     * @return IntSequence
     */
    protected function getInternalSequence(): IntSequence
    {
        return $this->sequence;
    }

    /**
     * @param IntSequence $sequence
     */
    protected function updateInternalSequence(IntSequence $sequence): void
    {
        $this->sequence = $sequence;
    }

    /**
     * @param string|null $value
     */
    private function bootUuid(?string $value): void
    {
        if ($value === null) {
            $this->touch();

            return;
        }

        $this->value = Uuid::fromString($value);
    }

    /**
     * @param int $value
     * @return static
     */
    public static function fromInt(int $value = 0): self
    {
        $instance = new self();

        $instance->updateInternalSequence(new IntSequence($value));
        $instance->touch();

        return $instance;
    }

    /**
     * @return void
     */
    private function touch(): void
    {
        $this->value = Uuid::fromInteger((string)$this->sequence->current());
    }

    /**
     * @return UuidInterface
     */
    public function current(): UuidInterface
    {
        return $this->value;
    }

    /**
     * @return void
     */
    public function next(): void
    {
        $this->sequence->next();
        $this->touch();
    }

    /**
     * @return string[]
     * @psalm-return {value: string}
     */
    public function __debugInfo(): array
    {
        return [
            'value' => $this->value->toString(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return $this->value->toString();
    }
}
