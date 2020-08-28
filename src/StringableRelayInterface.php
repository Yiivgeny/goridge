<?php

declare(strict_types=1);

namespace Spiral\Goridge;

/**
 * @deprecated since 2.5 and will be removed in 3.0. Please use {@see Stringable} interface instead.
 */
interface StringableRelayInterface extends \Stringable
{
    /**
     * @return string
     */
    public function __toString(): string;
}
